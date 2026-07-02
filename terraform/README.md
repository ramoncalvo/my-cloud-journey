# Terraform: SSO apps reales (AWS Cognito + Azure AD)

Provisiona por codigo las 2 de las 3 apps SSO que usa `auth/` (Cognito
User Pool + App Client, y el App Registration de Microsoft Entra ID), en
vez de crearlas a mano en la consola como describe `auth/SETUP.md`.
Pensado para crear y destruir facilmente: sin variables obligatorias, sin
estado compartido con nadie mas.

## Por que no incluye Google

Google Cloud no expone un recurso de Terraform (ni en el provider oficial
`google`/`google-beta`, ni en ninguno de HashiCorp) para crear un **OAuth
2.0 Client ID de tipo "Web application"** — ese paso siempre es manual en
la consola (ver seccion 5 de `auth/SETUP.md`). Los recursos que si existen
(`google_iap_client`, Identity Platform) resuelven otros casos de uso, no
este "Sign in with Google" simple. Por eso `/gcp` en todos los stacks se
sigue configurando a mano.

## Requisitos

- `terraform` >= 1.5
- Credenciales de AWS configuradas (`aws configure`, o variables
  `AWS_ACCESS_KEY_ID`/`AWS_SECRET_ACCESS_KEY`)
- Sesion de Azure iniciada: `az login` (el provider `azuread` la reutiliza)

## Uso

```bash
cd terraform
make apply     # terraform init + apply, pide confirmacion
make outputs   # imprime las variables listas para copiar a auth/.env
```

Copia la salida de `make outputs` dentro de `auth/.env` (los campos
`AWS_COGNITO_*` y `AZURE_*`; `GOOGLE_*` se configura a mano como siempre).

Para tirarlo todo abajo cuando termines de practicar:

```bash
make destroy
```

No hay dependencias externas ni recursos compartidos: cada `apply` crea
un User Pool y un App Registration nuevos (con nombre fijo
"multi-cloud-sso-lab" pero un dominio de Cognito con sufijo aleatorio), y
`destroy` los elimina por completo sin dejar residuos.

## Variables

Todas tienen defaults pensados para los stacks de `auth/` (puertos 8000,
8003, 8005); solo hace falta tocar `variables.tf` si cambias esos puertos
o quieres apuntar a otra region de AWS (`aws_region`).
