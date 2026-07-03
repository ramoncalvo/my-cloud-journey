# Ciberseguridad: fundamentos, ofensiva, defensiva y gobernanza

Complementa [02-auth-seguridad.md](02-auth-seguridad.md) (auth de aplicaciones),
[19-seguridad-cloud-onprem.md](19-seguridad-cloud-onprem.md) (seguridad de infraestructura)
y [20-vulnerabilidades-cloud-onprem.md](20-vulnerabilidades-cloud-onprem.md)
(vulnerabilidades + fixes). Este doc cubre lo que falta: criptografía,
frameworks, ofensiva/defensiva, respuesta a incidentes y gobernanza.

## 1. Criptografía — fundamentos

- **Cifrado simétrico vs asimétrico**

  1. Simétrico (AES): la misma clave cifra y descifra — rápido, se usa para cifrar el volumen real de datos (ej. el contenido de una sesión TLS ya establecida).
  2. Asimétrico (RSA, ECC): clave pública cifra, clave privada descifra (o al revés para firmas) — más lento, se usa para intercambiar la clave simétrica de forma segura al inicio de una conexión (el "handshake" de TLS) y para firmas digitales.

- **Hashing (no es cifrado, es unidireccional)**

  1. SHA-256 para verificar integridad de un archivo descargado (el hash publicado debe coincidir con el que tú calculas); `bcrypt`/`argon2` (hashing lento a propósito, con salt) para contraseñas — nunca SHA-256 puro para passwords porque es demasiado rápido de fuerza-brutear con GPUs.

- **PKI (Public Key Infrastructure) y certificados**

  1. Una Autoridad Certificadora (CA) firma el certificado de `midominio.com`, y el navegador confía en esa firma porque confía en la CA (preinstalada en su trust store) — así el usuario sabe que está hablando con el servidor real, no un impostor haciendo MITM.

- **TLS handshake (resumen práctico)**

  1. Cliente y servidor negocian versión/cifrados → servidor envía su certificado (clave pública) → cliente verifica la cadena de confianza → se genera una clave de sesión simétrica cifrada con la clave pública del servidor → a partir de ahí todo el tráfico va cifrado simétricamente (rápido).

- **Firmas digitales**

  1. Un release de software se firma con la clave privada del proveedor; cualquiera puede verificar con la clave pública que el binario no fue alterado ni viene de otra fuente — la base de la firma de artefactos mencionada en [18-cicd-github-actions.md](18-cicd-github-actions.md).

## 2. Frameworks y estándares

- **NIST Cybersecurity Framework (CSF)** — 5 funciones: Identify, Protect, Detect, Respond, Recover.

  1. Una empresa mapea sus controles existentes contra las 5 funciones del CSF para encontrar huecos — ej. "tenemos buen Protect (firewalls, IAM) pero Detect es débil (no hay SIEM)".

- **ISO 27001** — estándar internacional de gestión de seguridad de la información (ISMS), certificable.

  1. Un proveedor SaaS obtiene certificación ISO 27001 como requisito para venderle a clientes enterprise que exigen evidencia auditada de controles de seguridad.

- **CIS Controls** — lista priorizada y accionable de controles técnicos (menos abstracta que ISO).

  1. CIS Control 1 ("Inventory of Enterprise Assets") antes que cualquier otra cosa — no puedes proteger lo que no sabes que existe, el mismo principio que en parcheo on-premise.

- **PCI-DSS** — obligatorio si procesas/almacenas datos de tarjetas (ver también [06-pagos.md](06-pagos.md)).

  1. Un e-commerce delega el manejo de datos de tarjeta a Stripe/PayPal específicamente para reducir su alcance ("scope") de cumplimiento PCI-DSS, ya que nunca toca el número de tarjeta.

- **GDPR / LGPD / leyes de privacidad de datos**

  1. Un usuario europeo pide "derecho al olvido" — la aplicación debe poder borrar (o anonimizar) todos sus datos personales de producción *y* de los backups en un plazo definido.

## 3. Threat modeling

- **STRIDE** — Spoofing, Tampering, Repudiation, Information Disclosure, Denial of Service, Elevation of Privilege.

  1. Al diseñar el endpoint de login: ¿alguien puede *suplantar* a otro usuario (Spoofing)? ¿puede *modificar* el request en tránsito (Tampering)? ¿puede *negar* haber hecho una acción sin logs (Repudiation)? — se revisa cada categoría antes de escribir código, no después.

- **DREAD** — scoring de riesgo: Damage, Reproducibility, Exploitability, Affected users, Discoverability.

  1. Priorizar qué vulnerabilidad arreglar primero cuando hay 10 hallazgos de un pentest y recursos limitados — una con alto Damage y fácil Exploitability va antes que una difícil de explotar con impacto bajo.

- **Attack trees**

  1. Modelar visualmente todos los caminos posibles para "comprometer la cuenta de un usuario" (phishing → credential stuffing → SIM swapping → ...) para identificar qué rama es más barata de mitigar con mayor impacto.

## 4. Ofensiva: Red Team, pentesting

- **Vulnerability Assessment vs Penetration Testing**

  1. Un VA escanea automáticamente (Nessus, Qualys) y lista vulnerabilidades conocidas por CVE; un pentest va más allá — un humano intenta *explotarlas* encadenadas para demostrar impacto real (ej. de una XSS a robar la sesión de un admin).

- **Fases de un pentest**

  1. **Reconocimiento** (OSINT, subdominios, empleados en LinkedIn) → **Scanning** (nmap, puertos/servicios abiertos) → **Explotación** (Metasploit, exploits manuales) → **Post-explotación** (movimiento lateral, escalación de privilegios) → **Reporte** (hallazgos priorizados + remediación, el entregable que realmente le importa al cliente).

- **Herramientas comunes**

  1. **nmap** — descubrimiento de hosts/puertos/servicios en una red.
  2. **Burp Suite** — proxy para interceptar/modificar tráfico HTTP de una app web, encontrar IDOR/injection manualmente.
  3. **Metasploit** — framework de exploits conocidos, listos para lanzar contra un objetivo vulnerable.
  4. **Wireshark** — análisis de tráfico de red a nivel de paquete.

- **Bug bounty**

  1. Una empresa paga a investigadores externos (vía HackerOne/Bugcrowd) por reportar vulnerabilidades reales en su producción, con un scope y reglas claras — pentesting continuo crowdsourced en vez de solo auditorías puntuales anuales.

- **CTF (Capture The Flag)**

  1. Competencias tipo Hack The Box/TryHackMe/CTFtime para practicar explotación en entornos legales y controlados, la forma más común de entrenar habilidades ofensivas sin tocar sistemas reales sin autorización.

## 5. Defensiva: Blue Team, SOC, respuesta a incidentes

- **SOC (Security Operations Center)**

  1. Un equipo monitorea alertas de SIEM 24/7, triando cuáles son falsos positivos y cuáles requieren escalar a un analista senior o al equipo de respuesta a incidentes.

- **MITRE ATT&CK framework**

  1. Un SOC mapea cada alerta contra las tácticas/técnicas de ATT&CK (ej. "T1566 Phishing" → "T1078 Valid Accounts" → "T1021 Remote Services" para movimiento lateral), dando un lenguaje común para describir el comportamiento de un atacante, no solo "algo raro pasó".

- **IOCs (Indicators of Compromise) y Threat Intelligence**

  1. Un feed de threat intel comparte hashes de malware conocido, IPs de C2 (command & control), y dominios maliciosos; el SIEM/firewall bloquea automáticamente cualquier tráfico que coincida con esos indicadores.

- **Incident Response lifecycle (NIST)** — Preparación → Detección y Análisis → Contención → Erradicación y Recuperación → Lecciones Aprendidas.

  1. Ante un ransomware detectado: **Contención** = aislar las máquinas infectadas de la red inmediatamente (no apagarlas, se pierde evidencia forense en memoria); **Erradicación** = eliminar el malware y el vector de entrada; **Recuperación** = restaurar desde backups verificados; **Lecciones aprendidas** = documentar cómo entró y qué control faltaba.

- **Forense digital básico**

  1. Preservar una imagen bit-a-bit del disco antes de tocar nada (cadena de custodia), para poder analizar después qué hizo el atacante sin alterar la evidencia original — crítico si el incidente termina en proceso legal.

- **Purple Team**

  1. Red Team y Blue Team trabajan juntos en tiempo real: el Red Team ejecuta una técnica de ATT&CK específica y el Blue Team verifica en vivo si su detección la captura, iterando hasta cerrar el hueco — más colaborativo que un pentest tradicional de "caja negra".

## 6. Malware y ataques comunes

- **Tipos de malware**

  1. **Ransomware** (cifra archivos, pide rescate), **rootkit** (se esconde a nivel de kernel/firmware, sobrevive reinstalaciones parciales), **spyware** (roba datos silenciosamente), **botnet** (miles de equipos comprometidos controlados centralmente, usados para DDoS o minería), **worm** (se propaga solo, sin intervención humana, a diferencia de un virus que necesita un archivo ejecutado).

- **Ataques de contraseña**

  1. **Brute force** (probar todas las combinaciones), **dictionary attack** (probar contraseñas comunes/filtradas), **credential stuffing** (probar pares usuario/contraseña filtrados de *otro* breach, porque la gente reutiliza contraseñas), **rainbow tables** (tablas precalculadas de hashes, mitigadas con salt).

- **Ingeniería social**

  1. **Phishing** (email masivo genérico), **spear phishing** (dirigido a una persona específica con contexto real), **vishing** (por teléfono), **smishing** (por SMS), **pretexting** (crear una historia falsa creíble, ej. hacerse pasar por soporte IT), **tailgating** (colarse físicamente detrás de alguien con acceso).

## 7. GRC (Governance, Risk, Compliance)

- **Risk assessment / risk register**

  1. Un registro de riesgos documenta cada riesgo identificado (ej. "backup sin probar restauración"), su probabilidad, impacto, y el plan de mitigación con dueño y fecha — convierte "sabemos que esto es un problema" en algo rastreable y accionable.

- **Third-party risk management**

  1. Antes de integrar un proveedor SaaS nuevo, se revisa su certificación (SOC 2, ISO 27001) y se firma un DPA (Data Processing Agreement) — un proveedor comprometido puede ser la puerta de entrada a tu propia infraestructura (ataques de cadena de suministro).

- **Security awareness training**

  1. Simulacros de phishing internos trimestrales, con seguimiento de quién hace clic — la mayoría de los breaches empiezan con un humano, no con una vulnerabilidad técnica de día cero.

## 8. Certificaciones y ruta de aprendizaje (referencia)

- **Defensivo/generalista**: CompTIA Security+ (fundamentos) → CySA+ (analista SOC) → CISSP (gestión/arquitectura, requiere experiencia).
- **Ofensivo**: CEH (teórico, buena base) → OSCP (práctico, hands-on, el más respetado en la industria para pentesting) → OSCE/OSWE (especializaciones avanzadas).
- **Cloud security**: certificaciones específicas por proveedor (AWS Security Specialty, Azure Security Engineer, Google Professional Cloud Security Engineer).
- **Práctica gratuita**: TryHackMe y Hack The Box para ofensiva; un home lab con Wazuh/Security Onion (SIEM open source) para practicar el lado defensivo/SOC.
