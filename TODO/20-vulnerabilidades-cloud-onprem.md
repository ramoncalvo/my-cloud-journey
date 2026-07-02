# Vulnerabilidades conocidas: cloud y on-premise, con soluciones

## 1. OWASP Top 10 (aplican a ambos entornos)

- **Broken Access Control**

  Ejemplo: un endpoint `/api/orders/123` devuelve el pedido de cualquier ID sin verificar que pertenezca al usuario autenticado (IDOR — Insecure Direct Object Reference).
  **Solución**: verificar ownership/permiso en cada acceso a nivel de recurso, no solo autenticación; usar políticas RBAC/ABAC consistentes en cada endpoint, nunca confiar en que el frontend "no muestra el botón".

- **Cryptographic Failures**

  Ejemplo: contraseñas guardadas con MD5/SHA1 sin salt, o datos sensibles transmitidos por HTTP sin TLS.
  **Solución**: hashing de contraseñas con `bcrypt`/`argon2` (con salt automático), TLS 1.2+ obligatorio en todo tráfico, cifrado at-rest para datos sensibles (ver [19-seguridad-cloud-onprem.md](19-seguridad-cloud-onprem.md)).

- **Injection (SQL, NoSQL, Command)**

  Ejemplo: `query = f"SELECT * FROM users WHERE email = '{input}'"` permite que un input `' OR '1'='1` devuelva todos los usuarios.
  **Solución**: queries parametrizadas/prepared statements siempre (`cursor.execute(query, (input,))`), ORM en vez de SQL concatenado a mano, validación de input en el borde.

- **Insecure Design**

  Ejemplo: un flujo de "recuperar contraseña" que no limita intentos, permitiendo fuerza bruta sobre el código de verificación de 6 dígitos.
  **Solución**: rate limiting en endpoints sensibles, expiración corta de códigos OTP, threat modeling en la etapa de diseño (no parchear después).

- **Security Misconfiguration**

  Ejemplo: un servidor con el panel de administración de Django/Rails expuesto en producción con credenciales default, o modo debug activado exponiendo stack traces con paths internos.
  **Solución**: checklist de hardening por entorno, `DEBUG=False` en producción, escaneo automatizado de configuración (CSPM/CIS Benchmarks).

- **Vulnerable and Outdated Components**

  Ejemplo: una app usa una versión de `log4j` con la vulnerabilidad Log4Shell (CVE-2021-44228, RCE remota trivial).
  **Solución**: dependency scanning en CI (Dependabot/Snyk, ver [18-cicd-github-actions.md](18-cicd-github-actions.md)), SBOM (Software Bill of Materials) para saber exactamente qué versiones corren en producción, actualización proactiva.

- **Identification and Authentication Failures**

  Ejemplo: sesiones que nunca expiran, o un JWT sin validación de expiración/firma aceptado indefinidamente.
  **Solución**: expiración corta de tokens + refresh rotation, MFA en accesos sensibles, invalidación de sesión en logout real (no solo del lado cliente).

- **Software and Data Integrity Failures**

  Ejemplo: un pipeline de CI/CD descarga una dependencia de un CDN sin verificar su checksum/firma, permitiendo un ataque de "dependency confusion" o CDN comprometido.
  **Solución**: lockfiles (`package-lock.json`, `go.sum`) commiteados, verificación de firma de artefactos (Sigstore/cosign), mirrors privados de paquetes para dependencias críticas.

- **Security Logging and Monitoring Failures**

  Ejemplo: un atacante exfiltra datos durante semanas sin que nadie lo note porque no hay alertas sobre patrones de acceso anómalos.
  **Solución**: logging estructurado de eventos de seguridad (logins fallidos, cambios de permisos), SIEM con reglas de correlación, alertas automáticas ante anomalías.

- **Server-Side Request Forgery (SSRF)**

  Ejemplo: un endpoint que descarga una imagen desde una URL proporcionada por el usuario es usado para pedir `http://169.254.169.254/latest/meta-data/iam/security-credentials/` — el endpoint de metadata de la instancia cloud, robando las credenciales IAM de la máquina.
  **Solución**: whitelist de dominios/IPs permitidas para requests salientes iniciadas por input de usuario, bloquear rangos de IP internos/metadata (`169.254.169.254`) a nivel de red, usar IMDSv2 en AWS (requiere token, mitiga SSRF básico).

## 2. Vulnerabilidades específicas de cloud

- **Buckets/blobs de storage públicos por mala configuración**

  Ejemplo: un bucket S3 con "block public access" desactivado expone backups de base de datos completos indexables por Google.
  **Solución**: "block public access" habilitado por defecto a nivel de cuenta/organización, políticas de bucket explícitas, escaneo continuo (AWS Config Rule / Azure Policy) que alerta o revierte automáticamente configuraciones públicas.

- **Roles IAM sobre-permisivos ("just in case" permissions)**

  Ejemplo: un rol de Lambda con `"Action": "*", "Resource": "*"` porque "así no falla nunca" — si esa función se compromete, el atacante tiene control total de la cuenta.
  **Solución**: least privilege real (permisos mínimos necesarios, generados a partir del uso real con herramientas como AWS IAM Access Analyzer), revisión periódica de permisos no usados.

- **Secrets hardcodeados en código o variables de entorno planas**

  Ejemplo: una API key de Stripe commiteada por error en un repo público de GitHub, encontrada por bots que escanean commits en segundos.
  **Solución**: secret scanning en el repo (GitHub Secret Scanning, gitleaks) como pre-commit hook y en CI, gestor de secretos (Vault/KMS) en vez de `.env` en el código, rotación inmediata si se filtra alguno.

- **SSRF hacia el endpoint de metadata (IMDS)**

  Ver ejemplo en OWASP Top 10 arriba — es tan común en cloud que merece mención aparte. **Solución adicional**: IMDSv2 obligatorio (AWS), deshabilitar el endpoint de metadata si la instancia no lo necesita.

- **Contenedores con imágenes vulnerables o corriendo como root**

  Ejemplo: una imagen Docker basada en una versión vieja de Alpine con CVEs conocidas, corriendo el proceso principal como `root` dentro del contenedor (facilita escape a host si hay una vulnerabilidad del container runtime).
  **Solución**: escaneo de imágenes en el pipeline (Trivy, Grype), imágenes base mínimas (`distroless`, como se usa en este mismo lab para el stack de Go), `USER nonroot` explícito en el Dockerfile, imágenes reconstruidas regularmente (no "funciona, no la toco" por meses).

- **Kubernetes: dashboard expuesto, pods privilegiados, RBAC laxo**

  Ejemplo: el Kubernetes Dashboard expuesto sin autenticación en una IP pública (causa real de varios breaches conocidos de minería de criptomonedas en clusters comprometidos).
  **Solución**: dashboard nunca expuesto públicamente, RBAC granular por namespace/service account, Pod Security Standards (`restricted` en vez de `privileged` por defecto), network policies limitando tráfico entre pods.

- **Serverless: funciones over-privileged y event injection**

  Ejemplo: una Lambda disparada por un evento de S3 confía ciegamente en el nombre del archivo subido para construir una ruta de sistema de archivos, permitiendo path traversal.
  **Solución**: validar todo input del evento (aunque venga de "otro servicio propio de AWS", no es confiable por defecto), permisos de ejecución mínimos por función individual (no un rol compartido gigante para todas las Lambdas).

- **Dependency confusion / supply chain en paquetes internos**

  Ejemplo: una empresa tiene un paquete interno `empresa-utils` sin publicar en npm público; un atacante publica un paquete público con el mismo nombre y versión más alta, y el build lo instala por error desde el registro público.
  **Solución**: scoped packages (`@empresa/utils`), configurar el gestor de paquetes para priorizar siempre el registro privado, reservar el nombre también en el registro público como defensa adicional.

## 3. Vulnerabilidades específicas de on-premise

- **Sistemas sin parchear (el vector más común históricamente)**

  Ejemplo: WannaCry (2017) explotó EternalBlue, una vulnerabilidad de SMBv1 parcheada por Microsoft *meses* antes del ataque — las organizaciones afectadas simplemente no habían aplicado el parche.
  **Solución**: ciclo de parcheo obligatorio con SLA (crítico: 72h, alto: 7 días), inventario de activos actualizado (no puedes parchear lo que no sabes que existe).

- **Credenciales por defecto sin cambiar**

  Ejemplo: routers, cámaras IP, o paneles de administración de aplicaciones internas con `admin/admin` sin cambiar desde la instalación.
  **Solución**: checklist de hardening obligatorio en todo despliegue nuevo, escaneo periódico de la red interna buscando credenciales default.

- **Protocolos legacy inseguros habilitados**

  Ejemplo: SMBv1, Telnet, o FTP sin cifrar siguen habilitados "porque un sistema viejo los necesita", exponiendo credenciales en texto plano a cualquiera en la misma red.
  **Solución**: deshabilitar protocolos legacy salvo excepción documentada y aislada en su propia VLAN, migrar a SFTP/SSH.

- **Segmentación de red insuficiente (flat network)**

  Ejemplo: la red de una fábrica tiene los sistemas de planta (OT) en la misma red que las estaciones de trabajo administrativas — un phishing exitoso en administración compromete directamente sistemas industriales críticos.
  **Solución**: segmentación por VLANs con firewall entre zonas, principio de menor privilegio también a nivel de red (no todo dispositivo necesita hablar con todo dispositivo).

- **VPN sin MFA o con vulnerabilidades sin parchear**

  Ejemplo: varias campañas de ransomware recientes explotaron vulnerabilidades conocidas en appliances VPN corporativos (Fortinet, Pulse Secure) que no habían sido parchados, obteniendo acceso directo a la red interna.
  **Solución**: MFA obligatorio en VPN, parcheo prioritario de appliances de borde (son el primer objetivo), considerar Zero Trust Network Access (ZTNA) como reemplazo de VPN tradicional de perímetro.

- **Insider threats / accesos administrativos sin auditoría**

  Ejemplo: un ex-empleado conserva acceso VPN/AD activo semanas después de su salida porque el proceso de offboarding es manual y se olvidó.
  **Solución**: offboarding automatizado ligado al sistema de RRHH (deshabilitar cuentas el mismo día), revisión periódica de cuentas activas vs empleados activos, principio de mínimo privilegio también para empleados actuales.

- **Backups sin cifrar o sin probar restauración**

  Ejemplo: un ataque de ransomware cifra tanto los sistemas de producción como los backups porque ambos eran accesibles desde la misma red sin aislamiento — la empresa no tiene forma de recuperar datos sin pagar el rescate.
  **Solución**: backups aislados de la red de producción (air-gapped o con acceso de solo escritura desde producción), cifrado de backups, pruebas de restauración periódicas (un backup que nunca se probó restaurar no es un backup confiable).

## 4. Transversales (cloud + on-premise)

- **Ransomware**

  Ejemplo: cifrado masivo de archivos tras un compromiso inicial (phishing, VPN vulnerable, RDP expuesto), con demanda de rescate.
  **Solución**: backups aislados y probados, segmentación de red que limite movimiento lateral, EDR (Endpoint Detection and Response) en endpoints, plan de respuesta a incidentes documentado y ensayado.

- **Phishing / ingeniería social**

  Ejemplo: un empleado hace clic en un enlace que roba sus credenciales de Office 365, dando acceso al correo corporativo completo.
  **Solución**: MFA (mitiga la mayoría de los casos aunque la contraseña se filtre), entrenamiento continuo, filtros de email con sandboxing de adjuntos, DMARC/SPF/DKIM configurados correctamente en el dominio.

- **Movimiento lateral tras compromiso inicial**

  Ejemplo: un atacante compromete una estación de trabajo vía phishing y desde ahí escala hasta un Domain Controller porque no hay segmentación ni monitoreo interno.
  **Solución**: segmentación de red (Zero Trust), monitoreo de comportamiento anómalo interno (no solo perímetro), least privilege en cuentas de servicio, honeypots/honeytokens para detectar movimiento lateral temprano.
