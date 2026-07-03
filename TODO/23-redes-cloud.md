# Redes en la nube: firewalls, whitelists, balanceadores e IPs

## 1. Red virtual privada (VPC / VNet)

- **Concepto base**

  1. Una VPC (AWS/GCP) o VNet (Azure) es tu red privada aislada dentro de la nube — nadie fuera de ella puede alcanzar tus recursos salvo que tú abras una ruta explícita. Todo lo demás de este doc vive dentro de esa red.

- **Planeación de CIDR (rangos de IP)**

  1. Definir `10.0.0.0/16` para la VPC completa (65,536 IPs) y sub-dividirla en `10.0.1.0/24` (subnet pública), `10.0.2.0/24` (subnet privada de apps), `10.0.3.0/24` (subnet privada de datos) — planear el rango *antes* de crear nada, porque cambiar el CIDR de una VPC ya poblada de recursos es doloroso o imposible sin recrear todo.

- **Subnets públicas vs privadas**

  1. Una subnet pública tiene una ruta directa a un Internet Gateway (recursos ahí pueden tener IP pública); una subnet privada no tiene esa ruta — la base de datos vive en la privada y nunca es alcanzable directamente desde internet, solo desde el backend en la subnet intermedia.

- **Route tables**

  1. La tabla de rutas de la subnet pública tiene `0.0.0.0/0 → Internet Gateway`; la de la subnet privada tiene `0.0.0.0/0 → NAT Gateway` (para salir a internet, ej. descargar updates) pero nada entra desde fuera sin pasar por un balanceador explícito.

## 2. Firewalls: Security Groups vs Network ACLs

- **Security Groups (AWS) / NSGs (Azure) — stateful, a nivel de recurso**

  1. El security group de una instancia EC2 permite entrada en el puerto 443 desde `0.0.0.0/0` (tráfico público del sitio web) pero el de la base de datos solo permite el puerto 5432 desde el security group del backend, nunca desde `0.0.0.0/0`. "Stateful" significa que si permites la entrada, la respuesta de salida se permite automáticamente sin regla adicional.

- **Network ACLs — stateless, a nivel de subnet**

  1. Una capa adicional (opcional, casi nadie las toca a diario) que actúa a nivel de subnet completa, no por recurso individual; al ser stateless, tienes que permitir explícitamente tanto el tráfico de entrada como el de salida de la respuesta. Útil como "cinturón y tirantes" extra, ej. bloquear explícitamente una IP maliciosa conocida a nivel de toda la subnet de un golpe.

- **Cloud-native firewall gestionado (AWS Network Firewall, Azure Firewall, Cloud NGFW de GCP)**

  1. Inspección de paquetes con reglas tipo IDS/IPS (no solo puerto/IP como un security group), útil cuando necesitas cumplir un requisito de compliance que exige firewall con inspección profunda, no solo listas de puertos permitidos.

## 3. Whitelisting / listas de acceso (allowlisting)

- **Whitelisting de IPs para acceso administrativo**

  1. El panel de administración de un CMS solo acepta conexiones desde las IPs de la oficina (`203.0.113.0/24`) más la IP de la VPN corporativa — cualquier otra IP recibe connection refused antes de siquiera ver el login.

- **Whitelisting a nivel de aplicación vs a nivel de red**

  1. A nivel de red (security group): bloquea el paquete antes de llegar a la app. A nivel de aplicación (middleware que chequea `X-Forwarded-For`): más flexible (puedes loguear el intento) pero más caro en cómputo porque el request sí llegó hasta tu servidor.

- **Whitelisting dinámico (IP allowlisting como parte de un flujo de negocio)**

  1. Un banco permite que sus clientes corporativos configuren, desde su propio portal, qué IPs pueden llamar a su API — el whitelisting se vuelve un feature de producto, no solo una config de infra.

- **Riesgo de whitelisting por IP en cloud**

  1. Las IPs públicas de servicios cloud (ej. IPs de salida de Lambda, de un NAT Gateway compartido) pueden cambiar o ser compartidas con otros clientes del proveedor — whitelistear una IP elástica fija (ver sección 5) en vez de una IP dinámica es obligatorio para que esto sea confiable.

## 4. Balanceadores de carga

- **Capa 4 (transporte: TCP/UDP) vs Capa 7 (aplicación: HTTP)**

  1. Un Network Load Balancer (NLB en AWS) opera en L4: balancea por IP/puerto, ultra rápido, sin ver el contenido HTTP — bueno para TCP crudo o cuando necesitas preservar la IP origen del cliente. Un Application Load Balancer (ALB) opera en L7: puede rutear por path (`/api` → servicio A, `/admin` → servicio B), por header, por cookie de sesión (sticky sessions) — necesario para arquitecturas de microservicios detrás de un solo dominio.

- **Algoritmos de balanceo**

  1. Round robin (reparte parejo, bueno para requests de duración uniforme), least connections (manda al servidor con menos conexiones activas, mejor cuando la duración de request varía mucho, ej. un endpoint de reportes pesado vs uno simple), IP hash (mismo cliente siempre va al mismo backend, útil si no tienes sesión compartida entre instancias — ver el problema de sesión en memoria que vimos con Express/NestJS en el lab de auth).

- **Health checks**

  1. El balanceador pinga `/health` en cada instancia cada 10s; si una instancia falla 3 checks seguidos, se saca automáticamente del pool sin intervención humana — la base de la alta disponibilidad.

- **Sticky sessions (session affinity)**

  1. Un balanceador configurado para mandar siempre al mismo cliente a la misma instancia (vía cookie) — un parche útil si tu app tiene sesión en memoria (como el `MemoryStore` de Express que vimos antes), pero la solución correcta a largo plazo sigue siendo sesión compartida (Redis) para poder escalar sin esta dependencia.

- **Cross-zone load balancing**

  1. El balanceador reparte tráfico entre instancias en *todas* las zonas de disponibilidad por igual, no solo dentro de la zona donde entró la request — evita que una zona con menos instancias reciba desproporcionadamente menos tráfico.

- **Global load balancing (multi-región)**

  1. Un usuario en Europa se conecta al balanceador más cercano (vía Anycast o DNS geolocalizado) que lo rutea a la región `eu-west-1`; un usuario en Asia va a `ap-southeast-1` — reduce latencia y sirve como failover si una región entera cae.

## 5. Manejo de IPs

- **IP pública vs privada**

  1. La instancia de base de datos solo tiene IP privada (`10.0.2.15`), inalcanzable desde internet incluso si alguien tuviera la IP; el balanceador de carga tiene la única IP pública de toda la arquitectura.

- **IP elástica / estática (Elastic IP en AWS, Static IP en GCP/Azure)**

  1. Una IP pública que no cambia aunque reinicies o reemplaces la instancia detrás — necesaria si un cliente externo va a whitelistear tu IP de salida (sección 3), porque una IP dinámica normal cambia en cada reinicio.

- **NAT Gateway — IP compartida de salida para recursos privados**

  1. Todas las instancias de la subnet privada salen a internet (para descargar paquetes, llamar APIs externas) a través de la misma IP pública del NAT Gateway — desde afuera, todas se ven como una sola IP, y esa IP es la que un tercero whitelistearía si necesitas que tu backend llame a su API.

- **IP allocation en Kubernetes**

  1. Cada pod recibe su propia IP interna del rango del cluster (CNI plugin como Calico/Cilium la asigna); un Service de tipo `ClusterIP` da una IP virtual estable que persiste aunque los pods detrás mueran y se recreen con IPs nuevas.

- **IPv4 exhaustion e IPv6**

  1. Un CIDR `/16` de VPC se queda corto en una arquitectura con miles de microservicios/pods (cada uno con su IP); habilitar direccionamiento dual-stack IPv4/IPv6 evita tener que re-planear el rango completo de la red.

- **Egress control (limitar salida, no solo entrada)**

  1. Un security group de egress que solo permite salida hacia los dominios de APIs de terceros conocidas (o hacia un proxy que sí lo controla), en vez de `0.0.0.0/0` de salida abierto — mitiga exfiltración de datos si un servicio se compromete (relacionado con SSRF, ver [20-vulnerabilidades-cloud-onprem.md](20-vulnerabilidades-cloud-onprem.md)).

## 6. Conectividad privada y peering

- **VPC Peering / VNet Peering**

  1. Dos VPCs de dos equipos distintos dentro de la misma empresa se conectan directamente sin pasar por internet — el tráfico nunca sale de la red del proveedor cloud.

- **Transit Gateway (AWS) / Virtual WAN (Azure)**

  1. Con 20 VPCs necesitando comunicarse entre sí, el peering 1-a-1 requeriría ~190 conexiones; un Transit Gateway centraliza el enrutamiento como un hub, cada VPC solo se conecta una vez al hub.

- **PrivateLink / Private Endpoints**

  1. Un servicio accede a S3 o a un servicio de otro equipo sin que el tráfico cruce la red pública de internet, incluso estando en VPCs distintas — ya mencionado en [19-seguridad-cloud-onprem.md](19-seguridad-cloud-onprem.md), aquí es la pieza de red que lo hace posible.

- **VPN Site-to-Site / Direct Connect (AWS) / ExpressRoute (Azure) / Cloud Interconnect (GCP)**

  1. Conectar el datacenter on-premise a la VPC con una línea dedicada (Direct Connect) en vez de una VPN sobre internet público — más ancho de banda garantizado, latencia predecible, y el tráfico nunca toca internet público.

## 7. DNS

- **DNS gestionado (Route53, Azure DNS, Cloud DNS)**

  1. `api.miempresa.com` resuelve a la IP del balanceador de carga; cambiar de balanceador o de región solo requiere actualizar el registro DNS, no que los clientes cambien nada.

- **Routing policies avanzadas**

  1. Latency-based routing (manda al usuario a la región con menor latencia), weighted routing (90% del tráfico a la versión estable, 10% a canary — ver [12-devops-infra.md](12-devops-infra.md)), failover routing (si el health check del primario falla, DNS empieza a resolver al secundario automáticamente).

- **Private DNS (Route53 Private Hosted Zone, Azure Private DNS)**

  1. `db.internal.miempresa.com` solo resuelve dentro de la VPC, invisible desde internet — nombres internos legibles para servicios privados sin exponer su existencia públicamente.

## 8. CDN (relacionado, en el borde de la red)

- **CDN delante del origen**

  1. CloudFront/Azure CDN/Cloud CDN cachea assets estáticos (imágenes, JS, CSS) en edge locations cercanas al usuario, y también puede cachear respuestas de API con TTL corto, reduciendo carga directa sobre el balanceador/origen.

## 9. Bastion hosts / acceso administrativo seguro

- **Bastion host / jump box**

  1. La única forma de hacer SSH a una instancia en la subnet privada es primero conectarse al bastion (única máquina con IP pública, muy restringida y auditada), y desde ahí saltar a la instancia privada — nunca se expone SSH directo de las instancias privadas a internet.

- **Alternativa moderna sin bastion (AWS SSM Session Manager, Azure Bastion, IAP de GCP)**

  1. Acceso administrativo vía la API del proveedor cloud con autenticación IAM, sin necesidad de abrir el puerto 22 en ningún security group ni mantener un bastion como servidor adicional que parchear — reduce superficie de ataque a cero puertos SSH abiertos.

## 10. Tabla comparativa por nube

| Concepto | AWS | Azure | GCP |
|---|---|---|---|
| Red virtual | VPC | VNet | VPC |
| Firewall a nivel de recurso | Security Group (stateful) | NSG (stateful) | Firewall Rules (stateful) |
| Firewall gestionado con inspección | AWS Network Firewall | Azure Firewall | Cloud NGFW |
| WAF | AWS WAF | Azure WAF (en APIM/App Gateway/Front Door) | Cloud Armor |
| Balanceador L4 | Network Load Balancer | Azure Load Balancer | Network Load Balancer |
| Balanceador L7 | Application Load Balancer | Application Gateway | HTTP(S) Load Balancer |
| IP estática | Elastic IP | Static Public IP | Static External IP |
| NAT para subnets privadas | NAT Gateway | NAT Gateway | Cloud NAT |
| Peering | VPC Peering / Transit Gateway | VNet Peering / Virtual WAN | VPC Peering |
| Conectividad dedicada on-prem | Direct Connect | ExpressRoute | Cloud Interconnect |
| Acceso privado a servicios propios | PrivateLink | Private Link | Private Service Connect |
| Acceso admin sin bastion | SSM Session Manager | Azure Bastion | Identity-Aware Proxy (IAP) |
| DNS gestionado | Route53 | Azure DNS | Cloud DNS |
| CDN | CloudFront | Azure CDN / Front Door | Cloud CDN |
