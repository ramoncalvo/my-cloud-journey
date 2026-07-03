# Terraform: ruta de aprendizaje

Referencias cruzadas al `terraform/` de este mismo repo (provisiona Cognito
+ Azure AD para el lab de `auth/`) como ejemplo real, no solo teoría.

## 1. Conceptos fundamentales

- **HCL (HashiCorp Configuration Language)** — el lenguaje declarativo de los `.tf`.

  1. `resource "aws_cognito_user_pool" "this" { name = "multi-cloud-sso-lab" }` — declaras el *qué* (un User Pool con ese nombre), no el *cómo* (Terraform decide si crear, actualizar o no tocar nada).

- **Provider** — el plugin que traduce HCL en llamadas a la API de una nube/servicio concreto.

  1. `terraform/providers.tf` de este repo declara `aws`, `azuread` y `random` — cada uno habla con una API distinta (AWS API, Microsoft Graph, generador de números aleatorios locales).

- **Resource** — una pieza de infraestructura gestionada (se crea/actualiza/destruye).

  1. `resource "azuread_application" "this" { ... }` en `terraform/azure_ad.tf` — Terraform es dueño de este recurso: si lo borras del `.tf` y aplicas, lo destruye en Azure.

- **Data source** — leer algo que *ya existe* y no gestionas tú, sin crear nada.

  1. `data "azuread_client_config" "current" {}` en `terraform/azure_ad.tf` lee el tenant actual de la sesión de `az login`, no crea ningún tenant nuevo.

- **State** — el archivo (`terraform.tfstate`) donde Terraform recuerda qué creó y con qué IDs reales, para comparar contra tu `.tf` en el próximo `plan`.

  1. Si borras el state pero los recursos siguen existiendo en AWS, Terraform "olvida" que los creó e intentaría crearlos de nuevo (o fallar por nombre duplicado) — por eso el state es crítico y nunca se versiona en Git en texto plano (contiene secretos en claro).

## 2. Comandos básicos (el ciclo de vida)

```bash
terraform init      # descarga providers, inicializa el backend del state
terraform validate  # valida sintaxis y consistencia interna, sin tocar la nube
terraform fmt       # formatea el HCL al estilo canónico
terraform plan      # calcula el diff entre el .tf y el state actual (dry-run)
terraform apply     # ejecuta el plan, pide confirmación interactiva
terraform destroy   # elimina todo lo que Terraform gestiona en ese state
```

1. En este repo: `cd terraform && make apply` envuelve `init` + `apply`; `make destroy` limpia todo — ver `terraform/README.md`.

## 3. Variables y outputs

- **Variables con tipo y default**

  ```hcl
  variable "aws_region" {
    description = "Region de AWS donde crear el Cognito User Pool"
    type        = string
    default     = "eu-west-1"
  }
  ```
  Ejemplo real: `terraform/variables.tf` — todas tienen default, así `terraform apply` funciona sin pedir nada interactivamente.

- **Outputs**

  ```hcl
  output "aws_cognito_client_secret" {
    value     = aws_cognito_user_pool_client.this.client_secret
    sensitive = true
  }
  ```
  1. `terraform/outputs.tf` expone `aws_cognito_client_id`, `aws_cognito_domain`, etc. — `make outputs` los imprime listos para copiar a `auth/.env`. `sensitive = true` evita que el valor aparezca en logs de CI por accidente.

- **Validación de variables**

  ```hcl
  variable "environment" {
    type = string
    validation {
      condition     = contains(["dev", "staging", "prod"], var.environment)
      error_message = "environment debe ser dev, staging o prod."
    }
  }
  ```
  1. Evita que alguien despliegue a un typo (`"produ"`) que silenciosamente crea un entorno nuevo no planeado.

## 4. Loops y condicionales

- **`count`** — repetir un recurso N veces (simple, pero se reindexa raro si borras uno de en medio).

  1. `count = length(var.availability_zones)` para crear una subnet por AZ.

- **`for_each`** — repetir un recurso por cada elemento de un map/set (más seguro que `count`, cada instancia se identifica por clave, no por índice).

  1. En este repo, técnicamente `aws_callback_urls` es una lista pasada completa a un solo recurso (`callback_urls = var.aws_callback_urls`), pero si cada callback URL fuera su *propio* recurso gestionado, `for_each` sería la forma correcta de iterarlas sin que reordenar la lista destruya y recree recursos innecesariamente.

- **Expresiones condicionales**

  ```hcl
  instance_type = var.environment == "prod" ? "t3.large" : "t3.micro"
  ```

- **`dynamic` blocks** — generar bloques anidados repetidos dentro de un recurso.

  1. Generar un bloque `ingress {}` de un security group por cada puerto en una lista, sin repetir el bloque a mano por cada uno.

## 5. Módulos — reutilización

- **Módulo local**

  1. Si este lab creciera a multi-región, `terraform/modules/cognito-user-pool/` encapsularía User Pool + Domain + Client, y `main.tf` lo invocaría 3 veces (`eu-west-1`, `us-east-1`, ...) con `source = "./modules/cognito-user-pool"` y variables distintas.

- **Módulos del Registry (Terraform Registry)**

  1. En vez de escribir a mano los ~15 recursos de una VPC completa (subnets, route tables, NAT gateway, IGW), usar `terraform-aws-modules/vpc/aws`, un módulo público mantenido y probado por la comunidad.

## 6. Backends remotos y locking

- **Por qué no basta el state local**

  1. Con state local (`terraform.tfstate` en tu disco), un segundo ingeniero corriendo `apply` al mismo tiempo puede corromper el state o crear recursos duplicados — no hay forma de coordinar equipos.

- **Backend remoto (S3 + DynamoDB lock, Azure Storage, GCS, Terraform Cloud)**

  ```hcl
  terraform {
    backend "s3" {
      bucket         = "mi-empresa-tfstate"
      key            = "auth-lab/terraform.tfstate"
      region         = "eu-west-1"
      dynamodb_table = "tfstate-locks"
      encrypt        = true
    }
  }
  ```
  1. El bucket S3 guarda el state compartido; la tabla DynamoDB actúa como lock — si alguien está aplicando, un segundo `apply` espera o falla en vez de correr en paralelo y corromper todo.

- **Terraform Cloud/Enterprise**

  1. Backend gestionado con UI, historial de runs, aprobaciones manuales antes de `apply` en prod, y variables/secrets centralizados sin que cada dev tenga las credenciales cloud en su laptop.

## 7. Workspaces — múltiples entornos con el mismo código

  1. `terraform workspace new staging` / `terraform workspace new prod` — el mismo `.tf` gestiona 2 states completamente separados (`staging` y `prod`), cambiando solo de workspace activo, sin duplicar carpetas. Alternativa común: carpetas separadas por entorno (`envs/staging/`, `envs/prod/`) cuando la configuración diverge mucho entre entornos.

## 8. Importar infraestructura existente

  ```bash
  terraform import aws_cognito_user_pool.this eu-west-1_AbCdEfGhI
  ```
  1. Si alguien creó un User Pool a mano en la consola (como describe la sección "Manual" de `auth/SETUP.md`) y luego quieres que Terraform lo gestione, `import` lo vincula al state sin recrearlo — hay que escribir el `.tf` que coincida con la configuración real para que el siguiente `plan` no muestre cambios no deseados.

## 9. Seguridad y buenas prácticas

- **Nunca commitear el state ni `.tfvars` con secretos**

  1. `*.tfstate`, `*.tfstate.backup` y `.terraform/` van en `.gitignore` (como en este repo) — el state contiene valores sensibles en texto plano (ej. el `client_secret` de Cognito).

- **`.terraform.lock.hcl` SÍ se commitea**

  1. Congela las versiones exactas de providers usadas, para que `terraform init` en otra máquina/CI no descargue una versión distinta que cambie comportamiento sutilmente.

- **Least privilege en las credenciales que corren Terraform**

  1. El usuario/rol que ejecuta `terraform apply` en CI solo tiene permisos sobre los recursos que ese `.tf` gestiona, no `AdministratorAccess` — mismo principio que en [19-seguridad-cloud-onprem.md](19-seguridad-cloud-onprem.md).

- **Escaneo estático de IaC (tfsec, Checkov, Terrascan)**

  1. Un job de CI corre `tfsec .` y falla el pipeline si el `.tf` define un security group abierto a `0.0.0.0/0` en el puerto 22, antes de que eso llegue a aplicarse.

## 10. Testing

- **`terraform plan` en cada PR (dry-run obligatorio)**

  1. Un pipeline de GitHub Actions corre `terraform plan` en cada PR y publica el diff como comentario, para que el reviewer vea exactamente qué cambiaría en la infraestructura antes de aprobar el merge.

- **Terratest (tests de integración reales)**

  1. Un test en Go levanta el módulo en una cuenta de sandbox, verifica que el recurso creado responde como se espera (ej. el endpoint de Cognito resuelve), y destruye todo al final — valida comportamiento real, no solo sintaxis.

## 11. Integración con CI/CD

Ver [18-cicd-github-actions.md](18-cicd-github-actions.md) para el detalle de OIDC federation — aplica igual para el pipeline que corre Terraform:

```yaml
permissions:
  id-token: write
steps:
  - uses: aws-actions/configure-aws-credentials@v4
    with:
      role-to-assume: arn:aws:iam::123456789:role/terraform-ci-role
      aws-region: eu-west-1
  - run: terraform init
  - run: terraform plan -out=tfplan
  - run: terraform apply tfplan   # solo en push a main, con aprobacion de environment
```

## 12. Orden de aprendizaje sugerido (práctico, usando este repo)

1. Leer `terraform/providers.tf`, `variables.tf`, `aws_cognito.tf`, `outputs.tf` completos — son ~150 líneas totales, cubren el 80% de la sintaxis básica.
2. Correr `cd terraform && terraform init && terraform plan` (sin `apply` todavía) y leer el plan generado línea por línea.
3. Modificar una variable (ej. `aws_region`) y volver a correr `plan` — observar cómo cambia el diff.
4. `make apply` contra una cuenta AWS real (gratis dentro del free tier) y ver los recursos creados en la consola.
5. `make destroy` y confirmar que no queda nada — practicar el ciclo completo crear/destruir sin miedo, es la ventaja de IaC.
6. Una vez cómodo: intentar extraer el bloque de Cognito a un módulo local propio, y luego intentar montar un backend remoto S3 (aunque sea con un bucket de prueba) para practicar locking.
