# CI/CD: tutorial con GitHub Actions y variantes

## 1. Conceptos base

- **Workflow** — el archivo `.yml` completo (`.github/workflows/ci.yml`), disparado por un evento (`push`, `pull_request`, `schedule`, `workflow_dispatch`).
- **Job** — un grupo de steps que corre en un runner. Varios jobs corren en paralelo por defecto, salvo que declares `needs:`.
- **Step** — un comando o una `action` reutilizable dentro de un job.
- **Runner** — la máquina que ejecuta el job (`ubuntu-latest`, `self-hosted`, etc.).
- **Secrets** — valores sensibles (`${{ secrets.AWS_ROLE_ARN }}`) inyectados en runtime, nunca en el código.
- **Environments** — `production`/`staging` con protection rules (aprobación manual, secrets propios por entorno).
- **Artifacts** — archivos que un job genera (ej. el build) y otro job posterior descarga.
- **Matrix builds** — correr el mismo job con distintas combinaciones de parámetros (versiones de Node, SO, etc.) en paralelo.

## 2. Ejemplo de pipeline básico (build → test → deploy)

```yaml
name: CI/CD

on:
  push:
    branches: [main]
  pull_request:

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: "20"
          cache: "npm"
      - run: npm ci
      - run: npm test
      - run: npm run build
      - uses: actions/upload-artifact@v4
        with:
          name: dist
          path: dist/

  deploy:
    needs: test
    if: github.ref == 'refs/heads/main'
    runs-on: ubuntu-latest
    environment: production
    permissions:
      id-token: write   # necesario para OIDC, ver seccion 4
      contents: read
    steps:
      - uses: actions/download-artifact@v4
        with:
          name: dist
      - name: Deploy
        run: ./deploy.sh
```

## 3. Matrix builds — probar varias versiones/SOs en paralelo

```yaml
jobs:
  test:
    strategy:
      matrix:
        node: [18, 20, 22]
        os: [ubuntu-latest, macos-latest]
    runs-on: ${{ matrix.os }}
    steps:
      - uses: actions/setup-node@v4
        with:
          node-version: ${{ matrix.node }}
      - run: npm test
```

1. Una librería open source valida que funcione en Node 18/20/22 sobre Linux y macOS antes de cada release, sin escribir 6 jobs a mano.

## 4. Seguridad en el pipeline

- **OIDC federation en vez de credenciales estáticas** — la mejora de seguridad más importante de los últimos años en CI/CD.

  1. En vez de guardar `AWS_ACCESS_KEY_ID`/`AWS_SECRET_ACCESS_KEY` como secrets (credenciales de larga duración, si se filtran quedan válidas para siempre), el workflow pide un token OIDC de corta duración a GitHub y AWS/Azure/GCP lo intercambian por credenciales temporales de ese solo run:
     ```yaml
     permissions:
       id-token: write
     steps:
       - uses: aws-actions/configure-aws-credentials@v4
         with:
           role-to-assume: arn:aws:iam::123456789:role/gha-deploy-role
           aws-region: eu-west-1
     ```
     Azure usa `azure/login@v2` con `client-id`/`tenant-id`/`subscription-id` (sin secret); GCP usa `google-github-actions/auth@v2` con Workload Identity Federation. Ninguno de los 3 requiere una credencial almacenada.

- **Least privilege en el rol/service principal del deploy**

  1. El rol IAM que asume el pipeline solo tiene permisos sobre el bucket S3/servicio ECS específico que despliega, no `AdministratorAccess` — si el pipeline se compromete, el radio de daño es acotado.

- **Dependency scanning (Dependabot / Snyk / npm audit en CI)**

  1. Un job semanal abre un PR automático actualizando una dependencia con una CVE conocida; un job en cada PR falla el build si se introduce una dependencia con vulnerabilidad crítica.

- **SAST (Static Application Security Testing) en el pipeline**

  1. CodeQL (nativo de GitHub) o Semgrep corren en cada PR buscando patrones inseguros (SQL injection, secrets hardcodeados) antes del merge, no después en producción.

- **Firma de artefactos / supply chain security (Sigstore/cosign, SLSA)**

  1. Una imagen de contenedor se firma criptográficamente al buildearse; el cluster de producción rechaza desplegar imágenes sin firma válida, evitando que alguien inyecte una imagen maliciosa saltándose el pipeline.

- **Branch protection + required reviews + required status checks**

  1. `main` no acepta push directo, exige al menos 1 aprobación y que el job `test` haya pasado en verde, antes de permitir el merge.

- **Environment protection rules (aprobación manual para prod)**

  1. El deploy a `production` requiere que un humano apruebe manualmente en la UI de GitHub, aunque todo el pipeline hasta ahí sea automático — un "gate" humano antes del paso irreversible.

- **Secrets scoped por environment, no globales**

  1. El secret `STRIPE_SECRET_KEY` de producción solo es visible para jobs que corren bajo `environment: production`; un PR de un fork nunca puede leerlo, aunque el workflow lo referencie.

## 5. Deploy a microservicios / monorepo

- **Deploy independiente por servicio (path filtering)**

  1. Un monorepo con `services/orders/` y `services/payments/` — el workflow solo redeploya `orders` si hubo cambios dentro de `services/orders/**`, usando `paths:` en el trigger, evitando redeployar todo el monorepo por un cambio en un solo servicio.

- **Reusable workflows / composite actions**

  1. Los 12 microservicios del monorepo comparten un mismo `deploy-service.yml` reusable (`uses: ./.github/workflows/deploy-service.yml` con inputs), en vez de copiar-pegar el mismo YAML 12 veces.

- **GitOps (ArgoCD / Flux)**

  1. En vez de que GitHub Actions haga `kubectl apply` directamente, el pipeline solo actualiza un manifest en un repo de configuración; ArgoCD detecta el cambio y aplica el estado deseado al cluster — separa "quién construye la imagen" de "quién tiene acceso al cluster de producción".

- **Canary / blue-green orquestado desde CI/CD**

  1. El pipeline despliega la nueva versión al 5% del tráfico (vía un job que llama a la API del load balancer), espera 10 minutos monitoreando métricas de error, y si todo está bien, otro job promueve al 100%.

## 6. Deploy con autenticación por nube (ejemplos concretos)

| Nube | Acción de deploy | Autenticación recomendada |
|---|---|---|
| AWS | `aws-actions/amazon-ecs-deploy-task-definition`, deploy a Lambda vía `aws lambda update-function-code` | OIDC + IAM Role (`aws-actions/configure-aws-credentials`) |
| Azure | `azure/webapps-deploy`, `azure/aks-set-context` | OIDC + App Registration federado (`azure/login`) |
| GCP | `google-github-actions/deploy-cloudrun` | Workload Identity Federation (`google-github-actions/auth`) |

1. Deploy a Cloud Run en cada push a `main`:
   ```yaml
   - uses: google-github-actions/auth@v2
     with:
       workload_identity_provider: projects/123/locations/global/workloadIdentityPools/gha-pool/providers/gha-provider
       service_account: deployer@my-project.iam.gserviceaccount.com
   - uses: google-github-actions/deploy-cloudrun@v2
     with:
       service: my-api
       image: gcr.io/my-project/my-api:${{ github.sha }}
   ```

## 7. Variantes a GitHub Actions

| Herramienta | Cuándo se usa típicamente |
|---|---|
| **GitLab CI** | Si el código ya vive en GitLab; `.gitlab-ci.yml`, conceptos casi idénticos (stages en vez de jobs paralelos por defecto). |
| **CircleCI** | Equipos que quieren runners muy rápidos con cache agresivo out-of-the-box; configuración vía `config.yml` con "orbs" (paquetes reusables, como las Actions de GitHub). |
| **Jenkins** | Empresas grandes/legacy con infraestructura propia y necesidad de plugins muy específicos; más control pero mucho más mantenimiento operativo (servidor propio, plugins a actualizar). |
| **Azure DevOps Pipelines** | Empresas ya integradas al ecosistema Microsoft/Azure AD; YAML similar a GitHub Actions. |
| **AWS CodePipeline + CodeBuild** | Equipos 100% AWS que quieren todo el pipeline dentro de la cuenta AWS (IAM nativo, sin credenciales cruzando a un proveedor externo). |
| **Google Cloud Build** | Equivalente de Google, integración nativa con Artifact Registry/Cloud Run/GKE. |

La diferencia práctica entre todas: los conceptos (job, step, secret, artifact, trigger por rama) son casi universales — lo que cambia es la sintaxis YAML y cuánta integración nativa tienes con la nube donde despliegas. GitHub Actions gana por defecto si el código ya vive en GitHub, por la cantidad de actions ya publicadas en el Marketplace para casi cualquier integración.
