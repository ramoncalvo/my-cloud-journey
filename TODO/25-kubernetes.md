# Kubernetes: conceptos, operación y seguridad

## 1. Arquitectura del cluster

- **Control plane vs worker nodes**

  1. El control plane (API server, etcd, scheduler, controller manager) decide *qué* debería estar corriendo y *dónde*; los worker nodes (con `kubelet` + container runtime) ejecutan los pods reales. En un cluster gestionado (EKS/AKS/GKE) el proveedor opera el control plane por ti, tú solo gestionas los worker nodes (o ni eso, con modos serverless como Fargate/Autopilot).

- **etcd** — la base de datos clave-valor que guarda el estado deseado de todo el cluster.

  1. Si `etcd` se corrompe o se pierde sin backup, el cluster pierde la memoria de qué debería estar corriendo — es el componente más crítico a respaldar en un cluster self-managed.

- **API server** — el único punto de entrada; todo (kubectl, controllers, otros componentes) habla con el cluster a través de él.

  1. `kubectl apply -f deployment.yaml` es una llamada HTTP autenticada al API server, que valida el manifest y lo guarda en `etcd` como estado deseado.

- **Reconciliation loop (el patrón central de Kubernetes)**

  1. Un controller compara continuamente "estado deseado" (lo que declaraste) contra "estado actual" (lo que realmente corre) y actúa para cerrar la diferencia — si matas un pod manualmente de un Deployment con 3 réplicas, el ReplicaSet controller detecta que hay 2 y crea uno nuevo automáticamente, sin que nadie lo pida explícitamente.

## 2. Objetos de carga de trabajo

- **Pod** — la unidad mínima desplegable (uno o más contenedores que comparten red/storage).

  1. Un pod normalmente tiene un solo contenedor de aplicación, pero puede incluir un sidecar (ej. un proxy de service mesh) que comparte el mismo `localhost` de red que el contenedor principal.

- **Deployment** — gestiona ReplicaSets, permite rolling updates y rollback.

  1. `kubectl apply` con una nueva imagen actualiza el Deployment; Kubernetes reemplaza pods viejos por nuevos gradualmente (rolling update), manteniendo siempre un mínimo de réplicas disponibles — y `kubectl rollout undo` revierte a la versión anterior en segundos si algo sale mal.

- **StatefulSet** — para cargas con identidad estable y storage persistente (bases de datos, colas).

  1. Un cluster de Postgres o Kafka usa StatefulSet porque cada réplica necesita un nombre de red predecible (`kafka-0`, `kafka-1`) y su propio volumen persistente que la sigue aunque el pod se reprograme a otro nodo — a diferencia de un Deployment donde los pods son intercambiables.

- **DaemonSet** — un pod por cada nodo del cluster, automáticamente.

  1. Un agente de logging (Fluentd) o de monitoreo (node-exporter de Prometheus) que debe correr en *todos* los nodos sin excepción, incluyendo nodos nuevos que se agreguen después.

- **Job / CronJob**

  1. Un Job corre una tarea hasta completarse (ej. una migración de base de datos) y luego termina; un CronJob la programa periódicamente (ej. un backup nocturno) — el equivalente de un cron tradicional pero gestionado por el cluster.

## 3. Networking dentro del cluster

- **Service (ClusterIP, NodePort, LoadBalancer)**

  1. `ClusterIP` (default) — IP virtual estable solo accesible dentro del cluster, aunque los pods detrás mueran y cambien de IP. `NodePort` — expone un puerto fijo en cada nodo (poco usado en prod). `LoadBalancer` — provisiona automáticamente un balanceador cloud real (ALB, Azure LB) apuntando al Service, la forma estándar de exponer algo a internet.

- **Ingress + Ingress Controller**

  1. Un solo Ingress enruta `api.miapp.com/orders` → Service de pedidos y `api.miapp.com/payments` → Service de pagos, todo detrás de un único balanceador de carga en vez de uno por servicio — el Ingress Controller (nginx-ingress, ALB Controller, Traefik) es quien realmente traduce esas reglas a configuración real.

- **Network Policies** — firewall a nivel de pod, dentro del cluster.

  1. Por defecto todos los pods de un cluster pueden hablarse entre sí sin restricción; una NetworkPolicy limita que el pod de "reportería" solo pueda conectarse al pod de "base de datos de solo lectura", no al de "pagos" — el equivalente a Security Groups pero a nivel de pod/namespace en vez de VPC.

- **Service Mesh (Istio/Linkerd)** — ver también [08-arquitectura-resiliencia.md](08-arquitectura-resiliencia.md).

  1. Un sidecar proxy inyectado en cada pod maneja mTLS automático entre servicios, retries, circuit breaking y observabilidad, sin que cada microservicio implemente esa lógica en su propio código.

## 4. Configuración y secretos

- **ConfigMap**

  1. Variables de configuración no sensibles (nivel de log, feature flags, URL de un servicio interno) inyectadas como variables de entorno o archivos montados, separadas de la imagen — cambiar la config no requiere reconstruir/republicar la imagen.

- **Secret**

  1. Credenciales de base de datos o API keys — por defecto solo están codificadas en base64 (no cifradas) en `etcd`, así que en producción real se combinan con cifrado en reposo de `etcd` y, mejor aún, con un operador que sincroniza secretos desde un vault externo (External Secrets Operator + AWS Secrets Manager/Azure Key Vault), evitando que el secreto viva directamente como manifest de Kubernetes en Git.

- **Namespaces**

  1. Aislar `staging` y `production` (o equipos distintos) dentro del mismo cluster físico, cada uno con sus propias quotas de recursos, RBAC y network policies — más barato que un cluster separado por entorno, aunque con menos aislamiento que clusters físicamente distintos.

## 5. Storage

- **PersistentVolume (PV) y PersistentVolumeClaim (PVC)**

  1. Un StatefulSet de Postgres pide un PVC de 100GB; Kubernetes provisiona (dinámicamente, vía StorageClass) un disco real en la nube (EBS, Azure Disk, Persistent Disk) y lo asocia — el pod puede morir y recrearse en otro nodo, y el volumen lo sigue.

- **StorageClass**

  1. Define *qué tipo* de disco se provisiona automáticamente (SSD rápido para bases de datos transaccionales, HDD barato para logs de archivo) cuando se crea un PVC, sin que un humano cree el disco manualmente en la consola cloud.

## 6. Salud, escalado y recursos

- **Probes: liveness, readiness, startup**

  1. `livenessProbe` — si falla, Kubernetes mata y reinicia el contenedor (está colgado). `readinessProbe` — si falla, el pod se saca temporalmente del Service (no recibe tráfico) sin reiniciarlo, útil mientras calienta cache o termina de conectar a la DB. `startupProbe` — da más tiempo a apps con arranque lento antes de que el liveness empiece a evaluarlas y las mate prematuramente.

- **Resource requests y limits**

  1. `requests` (lo que el pod garantiza tener, usado por el scheduler para decidir en qué nodo cabe) vs `limits` (el tope que no puede exceder; si excede memoria, se mata con OOMKilled). Sin `requests` definidos, el scheduler puede sobre-empaquetar un nodo y causar contención impredecible entre pods vecinos.

- **Horizontal Pod Autoscaler (HPA)**

  1. Escala automáticamente de 3 a 15 réplicas de un Deployment cuando el uso de CPU supera 70%, y reduce de vuelta cuando baja — reacciona a carga real en vez de un número fijo de réplicas decidido a mano.

- **Cluster Autoscaler / Karpenter**

  1. Si el HPA quiere más pods pero no caben en los nodos actuales, el Cluster Autoscaler (o Karpenter en AWS, más moderno) provisiona nodos nuevos automáticamente — y los reduce cuando ya no se necesitan, para no pagar de más.

## 7. Seguridad

- **RBAC (Role-Based Access Control)**

  1. Un `Role` + `RoleBinding` limita a un desarrollador a solo poder leer pods/logs del namespace `staging`, sin poder tocar `production` ni borrar nada — mismo principio de least privilege que en IAM cloud.

- **Pod Security Standards (`restricted` vs `privileged`)**

  1. Por defecto se aplica el perfil `restricted`: no se permite correr contenedores como root, no se permite montar el socket de Docker del host, no se permiten capabilities peligrosas — evita que un pod comprometido escale a control del nodo completo.

- **Service Accounts** — la identidad que usa un pod para hablar con el API server (o con servicios cloud externos vía Workload Identity Federation).

  1. Un pod que necesita leer un bucket S3 usa un Service Account vinculado (IRSA en EKS) a un rol IAM con permisos mínimos sobre ese bucket específico — sin credenciales de AWS hardcodeadas en ningún Secret.

- **Escaneo de manifests (kube-score, Polaris, OPA Gatekeeper)**

  1. Un policy engine (OPA Gatekeeper) rechaza automáticamente cualquier `kubectl apply` que intente crear un pod `privileged: true` o sin límites de recursos definidos, antes de que llegue a existir en el cluster.

## 8. Despliegue y operación

- **Rolling updates y rollback**

  1. `kubectl set image deployment/api api=myapp:v2` reemplaza pods gradualmente respetando `maxUnavailable`/`maxSurge`; `kubectl rollout undo deployment/api` vuelve a `v1` si el health check de los nuevos pods empieza a fallar.

- **Helm — gestor de paquetes de Kubernetes**

  1. En vez de mantener 15 archivos YAML por microservicio, un Helm chart parametriza todo con `values.yaml` (`replicas: 3`, `image.tag: v2`) — instalar/actualizar es `helm upgrade myapp ./chart -f values-prod.yaml`, con versionado e historial de releases incluido.

- **GitOps (ArgoCD / Flux)** — ver también [18-cicd-github-actions.md](18-cicd-github-actions.md).

  1. En vez de que CI haga `kubectl apply` directo (requiere credenciales del cluster en el pipeline), ArgoCD vive *dentro* del cluster y sincroniza automáticamente contra un repo Git — el pipeline solo actualiza el repo de manifests, nunca tiene acceso directo de escritura al cluster.

- **Init containers y sidecars**

  1. Un init container espera a que una migración de base de datos termine antes de que el contenedor principal arranque; un sidecar (ej. un proxy de service mesh, o un exportador de métricas) corre junto al contenedor principal durante toda la vida del pod.

## 9. Kubernetes gestionado por nube

| Concepto | AWS | Azure | GCP |
|---|---|---|---|
| Servicio gestionado | EKS | AKS | GKE |
| Modo sin gestionar nodos | Fargate (para EKS) | AKS Automatic | Autopilot |
| Identidad de pod → cloud IAM | IRSA (IAM Roles for Service Accounts) | Workload Identity (Azure AD) | Workload Identity |
| Registro de imágenes nativo | ECR | ACR | Artifact Registry |
| Autoescalado de nodos moderno | Karpenter | Cluster Autoscaler / Node Autoprovisioning | Node Auto-provisioning |
| Ingress nativo del proveedor | AWS Load Balancer Controller | Application Gateway Ingress Controller | GKE Ingress (Cloud Load Balancing) |

## 10. Relación con este mismo repo

El lab de `auth/` corre hoy con `docker compose` (pensado para simplicidad local). El salto natural a Kubernetes sería: un Deployment por stack (`python`, `nestjs`, `go`), un Service `ClusterIP` cada uno, un Ingress único enrutando `/aws`, `/azure`, `/gcp` de cada stack a puertos distintos si se quisiera exponer los 3 bajo un solo dominio, y los valores de `auth/.env` migrados a un Secret (idealmente sincronizado desde AWS Secrets Manager/Azure Key Vault vía External Secrets Operator, no como manifest plano en Git).
