
* **S3** — objetos
* **DynamoDB** — NoSQL
* **SQS** / **SNS** — colas y notificaciones
* **Lambda** — funciones (ejecutadas en contenedores Docker locales)
* **API Gateway**
* **CloudFormation** — para desplegar stacks completos localmente
* **IAM** (soporte parcial, no aplica permisos reales)
* **Secrets Manager**
* **SSM (Parameter Store)**
* **Kinesis**
* **SES** (envío de correo simulado)
* **Step Functions**
* **EventBridge**
* **CloudWatch Logs** (básico)
* **Route53** (básico)

## Servicios relevantes en AWS / Azure / GCP para full-stack senior + DevOps

### Cómputo

| Categoría | AWS | Azure | GCP |
|---|---|---|---|
| VMs | EC2 | Virtual Machines | Compute Engine |
| Contenedores gestionados | ECS / Fargate | Container Apps / ACI | Cloud Run |
| Kubernetes | EKS | AKS | GKE |
| Serverless / FaaS | Lambda | Azure Functions | Cloud Functions |
| PaaS para apps web | Elastic Beanstalk | App Service | App Engine |

### Storage y bases de datos

| Categoría | AWS | Azure | GCP |
|---|---|---|---|
| Objetos | S3 | Blob Storage | Cloud Storage |
| SQL gestionado | RDS (Postgres/MySQL) | Azure SQL / Database for PostgreSQL | Cloud SQL |
| SQL serverless | Aurora Serverless | Azure SQL Serverless | AlloyDB / Cloud SQL |
| NoSQL documento | DynamoDB | Cosmos DB | Firestore |
| Cache | ElastiCache (Redis) | Azure Cache for Redis | Memorystore |

### Redes y entrega de contenido

| Categoría | AWS | Azure | GCP |
|---|---|---|---|
| VPC | VPC | Virtual Network | VPC |
| Balanceador de carga | ELB/ALB | Load Balancer | Cloud Load Balancing |
| CDN | CloudFront | Azure CDN / Front Door | Cloud CDN |
| DNS | Route53 | Azure DNS | Cloud DNS |
| API Gateway | API Gateway | API Management | API Gateway |

### DevOps / CI-CD / IaC

| Categoría | AWS | Azure | GCP |
|---|---|---|---|
| CI/CD nativo | CodePipeline/CodeBuild | Azure DevOps / GitHub Actions | Cloud Build |
| IaC nativo | CloudFormation | ARM / Bicep | Deployment Manager |
| IaC multi-cloud | **Terraform** (cubre las 3 nubes, el más usado en la industria) | | |
| Registro de contenedores | ECR | ACR | Artifact Registry |
| Secretos | Secrets Manager / SSM Parameter Store | Key Vault | Secret Manager |
| Observabilidad | CloudWatch | Azure Monitor | Cloud Monitoring / Logging |

### Identidad y seguridad

| Categoría | AWS | Azure | GCP |
|---|---|---|---|
| IAM | IAM | Azure AD (Entra ID) | Cloud IAM |
| Auth para apps (usuarios finales) | Cognito | Azure AD B2C | Identity Platform / Firebase Auth |

### Mensajería / eventos

| Categoría | AWS | Azure | GCP |
|---|---|---|---|
| Colas | SQS | Service Bus / Queue Storage | Pub/Sub |
| Eventos | EventBridge | Event Grid | Eventarc |

### Prioridad recomendada para el lab

1. Cómputo serverless + contenedores (Lambda/Functions/Cloud Functions, ECS-Fargate/Container Apps/Cloud Run, EKS/AKS/GKE)
2. **Terraform** como IaC transversal a las 3 nubes
3. S3/Blob/GCS + CDN para servir frontend estático
4. RDS/Azure SQL/Cloud SQL + una NoSQL (DynamoDB/Cosmos/Firestore)
5. CI/CD con GitHub Actions
6. IAM y Secrets Manager
