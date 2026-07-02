# Seguridad: cloud, on-premise e híbrido

## 1. Shared Responsibility Model

- **Qué cubre el proveedor vs qué cubres tú**

  1. AWS/Azure/GCP son responsables de la seguridad *del* cloud (datacenters, hipervisor, red física); tú eres responsable de la seguridad *en* el cloud (configuración de IAM, cifrado de tus datos, parches del SO si usas VMs, reglas de firewall). Un bucket S3 público por mala configuración es responsabilidad tuya, no de AWS.
  2. En on-premise, **toda** la pila es tu responsabilidad — desde la seguridad física del datacenter hasta el parcheo del hypervisor. Es la razón por la que migrar a cloud reduce (no elimina) superficie de responsabilidad operativa.

## 2. Identidad y acceso (IAM)

- **Least privilege**

  1. Un pipeline de CI/CD tiene un rol que solo puede desplegar a un servicio ECS específico, no `AdministratorAccess` (ver también [18-cicd-github-actions.md](18-cicd-github-actions.md)).

- **Roles/Service Principals en vez de usuarios con contraseña para servicios**

  1. Una Lambda asume un `execution role` con permisos exactos a las tablas DynamoDB que necesita, sin credenciales de usuario embebidas en el código.

- **Workload Identity Federation (evitar credenciales de larga duración)**

  1. Un servicio corriendo en GKE se autentica contra otros servicios de GCP usando su identidad de Kubernetes federada, sin un JSON de service account key descargado y guardado en un secret.

- **MFA obligatorio para accesos humanos privilegiados**

  1. La cuenta root de AWS y cualquier usuario con permisos de IAM tienen MFA forzado; un compromiso de contraseña sola no basta para tomar control de la cuenta.

- **Just-in-time access / acceso temporal**

  1. Un ingeniero pide acceso de `SSH` a un servidor de producción por 2 horas vía un sistema de aprobación (ej. AWS SSM Session Manager con políticas temporales), en vez de tener acceso permanente "por si acaso".

## 3. Seguridad de red

- **Segmentación (VPC/VNet, subnets públicas vs privadas)**

  1. La base de datos vive en una subnet privada sin ruta a internet; solo el backend en la subnet privada puede alcanzarla, y el backend a su vez solo es alcanzable desde el load balancer en la subnet pública.

- **Security Groups / NSGs — least privilege también a nivel de red**

  1. El security group de la base de datos solo permite tráfico entrante en el puerto 5432 desde el security group del backend, no desde `0.0.0.0/0`.

- **Private endpoints / PrivateLink**

  1. Un servicio en AWS accede a S3 sin que el tráfico salga a la red pública de internet, usando un VPC Endpoint — reduce superficie de ataque y a veces también costo de data transfer.

- **WAF (Web Application Firewall)**

  1. Un WAF delante de la API pública bloquea patrones conocidos de SQL injection/XSS a nivel de red, antes de que la petición llegue a la aplicación — una capa adicional, no un reemplazo de validar inputs en el código.

- **DDoS protection (AWS Shield, Azure DDoS Protection, Cloud Armor)**

  1. Un e-commerce activa protección DDoS gestionada antes de una campaña de alto tráfico (Black Friday) para absorber picos maliciosos sin caer.

## 4. Seguridad de APIs — APIM y equivalentes

*(ver también la comparativa completa de costos y features en la conversación sobre API Gateway — este doc se enfoca en las capacidades de seguridad)*

- **Validación de JWT en el gateway (offload de auth)**

  1. Azure APIM valida el JWT contra el `.well-known/openid-configuration` del IdP con la política `validate-jwt`, antes de que la request llegue al backend — el backend nunca ve un token inválido.

- **mTLS entre gateway y backend**

  1. El API Gateway y los microservicios internos se autentican mutuamente con certificados de cliente, para que ni siquiera un atacante dentro de la red interna pueda hablarle directamente a un microservicio sin certificado válido.

- **Rate limiting / quotas por API key o por cliente**

  1. APIM aplica una política de `rate-limit-by-key` distinta por plan de cliente (100 req/min free, 10k/min enterprise), sin que el backend implemente esa lógica.

- **API keys + rotación**

  1. Claves de API para integraciones B2B con expiración forzada cada 90 días, revocables individualmente si una integración específica se ve comprometida.

- **Threat protection / anomaly detection en el gateway**

  1. El gateway detecta un patrón de scraping (miles de requests secuenciales enumerando IDs) y bloquea la IP automáticamente, antes de que llegue a la capa de negocio.

## 5. Gestión de secretos y cifrado

- **Vault / cloud KMS en vez de variables de entorno planas**

  1. Las credenciales de la base de datos se leen de AWS Secrets Manager/Azure Key Vault/HashiCorp Vault en runtime, con rotación automática, en vez de estar hardcodeadas en un `.env` versionado por error.

- **Cifrado en reposo (at rest)**

  1. Un bucket S3 y su base de datos RDS tienen cifrado habilitado por defecto con una KMS key gestionada, para que un disco robado o un snapshot filtrado no exponga datos en claro.

- **Cifrado en tránsito (in transit)**

  1. Todo el tráfico interno entre microservicios usa TLS, no solo el tráfico público — asumir que la red interna es "segura por defecto" es un error común.

- **Rotación de claves**

  1. Las llaves de cifrado de una KMS rotan automáticamente cada año sin necesidad de re-cifrar los datos existentes (el proveedor maneja versionado de claves de forma transparente).

## 6. Governance y compliance

- **Policy as Code (OPA, AWS Config Rules, Azure Policy)**

  1. Una regla de Azure Policy bloquea automáticamente la creación de un storage account sin cifrado o con acceso público habilitado, antes de que el recurso siquiera se cree.

- **CSPM (Cloud Security Posture Management)**

  1. Una herramienta (ej. Wiz, Prisma Cloud, AWS Security Hub) escanea continuamente la cuenta cloud buscando configuraciones inseguras (buckets públicos, roles sobre-permisivos, cifrado deshabilitado) y las reporta con prioridad de riesgo.

- **Auditoría (CloudTrail / Azure Activity Log / Cloud Audit Logs)**

  1. Cada llamada a la API de AWS (quién, cuándo, qué acción) queda registrada de forma inmutable, permitiendo reconstruir exactamente qué pasó tras un incidente de seguridad.

## 7. Seguridad on-premise

- **Segmentación de red (DMZ)**

  1. Los servidores expuestos a internet (web, email) viven en una DMZ separada de la red interna donde están los sistemas financieros/ERP, con firewall entre ambas zonas.

- **Gestión de parches**

  1. Un ciclo mensual obligatorio de parcheo de SO y aplicaciones, con ventanas de mantenimiento programadas — el vector de ataque más común en breaches on-premise sigue siendo software sin parchear (ej. exploits conocidos de meses/años de antigüedad).

- **Active Directory endurecido**

  1. Deshabilitar protocolos legacy inseguros (NTLMv1, SMBv1), políticas de contraseña fuertes, y segmentación de grupos administrativos (Tier 0/1/2) para que comprometer una estación de trabajo normal no dé camino directo a Domain Admin.

- **VPN / acceso remoto seguro**

  1. Acceso remoto vía VPN con MFA obligatorio, en vez de exponer RDP directamente a internet (una de las causas más comunes de ransomware en PyMEs).

- **SIEM on-premise (Splunk, ELK, QRadar)**

  1. Logs de firewall, AD, y servidores centralizados en un SIEM con reglas de correlación que alertan ante patrones anómalos (ej. un usuario haciendo login desde 2 países en 10 minutos).

- **Seguridad física**

  1. Control de acceso biométrico al datacenter, cámaras, y racks con cerradura — sigue siendo relevante en on-premise, irrelevante en cloud público (ya lo maneja el proveedor).

## 8. Híbrido y Zero Trust

- **Conectividad híbrida segura (VPN site-to-site, ExpressRoute/Direct Connect)**

  1. Una empresa conecta su datacenter on-premise a su VPC de AWS vía Direct Connect (línea dedicada, no internet público), para que el tráfico entre ambos entornos nunca cruce la red pública.

- **Federación de identidad on-prem ↔ cloud (Azure AD Connect)**

  1. Los usuarios de Active Directory on-premise se sincronizan con Azure AD, permitiendo SSO único tanto para apps on-prem como SaaS/cloud, sin mantener 2 sistemas de identidad separados.

- **Zero Trust Architecture**

  1. En vez de confiar en "estar dentro de la red corporativa" (perímetro clásico), cada request se autentica y autoriza individualmente sin importar el origen — un empleado remoto y uno en la oficina pasan por la misma verificación estricta. Principio: "never trust, always verify".

- **CASB (Cloud Access Security Broker)**

  1. Visibilidad y control sobre qué apps SaaS usan los empleados (shadow IT) y aplicación de políticas de DLP (data loss prevention) entre el usuario y el servicio cloud, típico en empresas reguladas (banca, salud).
