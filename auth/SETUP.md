# Setup del lab de SSO multi-cloud

El mismo lab implementado en 4 stacks para comparar frameworks, todos con
el SSO (OIDC) real de cada nube:

| Stack | Carpeta | Puerto |
|---|---|---|
| Python (FastAPI + Authlib, clean architecture por dominios) | `python/` | 8000 |
| Node (NestJS + openid-client) | `nestjs/` | 8003 |
| Node (Express + openid-client) | `express/` | 8004 |
| PHP vanilla (servidor embebido + curl) | `php/` | 8006 |

Cada uno expone 3 vistas publicas (`/aws`, `/azure`, `/gcp`) y 3 privadas
(`/aws/private`, `/azure/private`, `/gcp/private`). Necesitas cuenta
gratuita en las 3 nubes para registrar la app — no hay coste, pero no es
un simulador: son registros reales de aplicaciones OAuth/OIDC, compartidos
por los 4 stacks (mismas credenciales, distintas redirect URIs por puerto).

Las apps de Cognito y Azure AD se pueden crear/destruir con un comando via
Terraform — ver `../terraform/README.md`. Google no tiene equivalente por
Terraform (ver esa misma nota) y se configura a mano, seccion 5 de aqui.

## 1. Requisitos previos

- Docker + Docker Compose (los 4 stacks corren en contenedor, no hace falta Python/Node/PHP instalados en local)
- Una cuenta AWS (free tier, requiere tarjeta solo para verificacion de identidad)
- Una cuenta Microsoft/Azure (puedes usar el tenant gratuito que se crea al abrir un Azure free trial, o un tenant de Microsoft Entra ID gratuito sin trial)
- Una cuenta de Google + proyecto en Google Cloud Console (gratis, la creacion de credenciales OAuth no requiere billing activado)

## 2. Redirect URIs por stack

Los 4 stacks usan la misma convencion, `/auth/{cloud}/callback`. Hay que
dar de alta las 4 URLs de cada nube en el proveedor correspondiente (o
dejar que Terraform lo haga por ti para AWS/Azure, ver arriba):

| Nube | Python (8000) | NestJS (8003) | Express (8004) | PHP (8006) |
|---|---|---|---|---|
| AWS Cognito | `.../auth/aws/callback` | `.../auth/aws/callback` | `.../auth/aws/callback` | `.../auth/aws/callback` |
| Azure AD | `.../auth/azure/callback` | `.../auth/azure/callback` | `.../auth/azure/callback` | `.../auth/azure/callback` |
| Google | `.../auth/gcp/callback` | `.../auth/gcp/callback` | `.../auth/gcp/callback` | `.../auth/gcp/callback` |

(sustituye `...` por `http://localhost:PUERTO` segun la columna, ej.
`http://localhost:8006/auth/aws/callback` para PHP).

## 3. AWS Cognito (SSO para `/aws`)

**Opcion rapida:** `cd ../terraform && make apply && make outputs` crea el
User Pool + App Client con las 4 callback URLs ya configuradas.

**Manual:**

1. Consola AWS -> **Cognito** -> **User pools** -> **Create user pool**.
2. Sign-in options: email. El resto puedes dejarlo por defecto.
3. En **App integration**:
   - Crea un **dominio de Cognito** (Hosted UI), ej: `tu-prefijo.auth.eu-west-1.amazoncognito.com`.
   - Crea un **App client** (tipo confidencial, con client secret).
   - En el App client, configura:
     - **Allowed callback URLs**: las 4 URLs de AWS Cognito de la tabla anterior (una por linea).
     - **Allowed sign-out URLs**: `http://localhost:PUERTO/aws` para cada uno de los 4 puertos.
     - **OAuth grant types**: Authorization code grant
     - **OpenID scopes**: `openid`, `email`, `profile`
4. Anota: region, User pool ID, Client ID, Client secret y el dominio del Hosted UI.
5. Rellena en `.env` (en `auth/`, compartido por los 4 stacks):
   ```
   AWS_COGNITO_REGION=...
   AWS_COGNITO_USER_POOL_ID=...
   AWS_COGNITO_CLIENT_ID=...
   AWS_COGNITO_CLIENT_SECRET=...
   AWS_COGNITO_DOMAIN=https://tu-prefijo.auth.eu-west-1.amazoncognito.com
   ```

## 4. Azure AD / Microsoft Entra ID (SSO para `/azure`)

**Opcion rapida:** incluido en el mismo `make apply` de Terraform (arriba).

**Manual:**

1. [portal.azure.com](https://portal.azure.com) -> **Microsoft Entra ID** -> **App registrations** -> **New registration**.
2. Redirect URIs (tipo **Web**): agrega las 4 URLs de Azure AD de la tabla anterior.
3. Anota **Application (client) ID** y **Directory (tenant) ID** desde la pagina Overview.
4. **Certificates & secrets** -> **New client secret** -> copia el **value** (solo se muestra una vez).
5. Los permisos por defecto (`User.Read`, `openid`, `profile`, `email`) ya son suficientes.
6. Rellena en `.env`:
   ```
   AZURE_TENANT_ID=...
   AZURE_CLIENT_ID=...
   AZURE_CLIENT_SECRET=...
   ```

## 5. Google OAuth (SSO para `/gcp`)

Siempre manual (no hay recurso de Terraform para esto, ver nota arriba):

1. [console.cloud.google.com](https://console.cloud.google.com) -> crea un proyecto nuevo (o usa uno existente).
2. **APIs & Services** -> **OAuth consent screen**: tipo **External**, modo **Testing**, agrega tu email como test user.
3. **APIs & Services** -> **Credentials** -> **Create Credentials** -> **OAuth client ID** -> tipo **Web application**.
4. **Authorized redirect URIs**: agrega las 4 URLs de Google de la tabla anterior.
5. Anota **Client ID** y **Client secret**.
6. Rellena en `.env`:
   ```
   GOOGLE_CLIENT_ID=...
   GOOGLE_CLIENT_SECRET=...
   ```

## 6. Ejecutar los 4 stacks

Todo lo de este lab vive bajo `auth/`, ejecuta los comandos desde ahi:

```bash
cd auth
cp .env.example .env   # si no existe ya; rellena los valores de las 3 nubes
python3 -c "import secrets; print(secrets.token_hex(32))"  # pega el resultado en SESSION_SECRET_KEY

make up      # docker compose build + up -d de los 4 stacks
```

- `http://localhost:8000` — Python (FastAPI)
- `http://localhost:8003` — NestJS
- `http://localhost:8004` — Express
- `http://localhost:8006` — PHP vanilla

En cada una: `/aws`, `/azure`, `/gcp` son publicas; `/aws/private`,
`/azure/private`, `/gcp/private` piden login SSO contra su nube.

Para levantar/parar un stack suelto en vez de los 4, usa `STACK=`:
```bash
make up STACK=php
make down STACK=php
make logs STACK=php
```

Otros comandos: `make down`, `make restart`, `make status`, `make logs`.

## Notas

- Sin rellenar las credenciales de una nube, su vista publica funciona pero el login de esa nube fallara (el proveedor rechazara un client_id vacio/invalido). Puedes configurar las nubes una a una.
- Nada de esto tiene coste: Cognito, App registrations de Entra ID y credenciales OAuth de Google son gratuitas de crear y usar en bajo volumen.
- Los 4 stacks comparten el mismo `.env`: mismas credenciales de cada nube, cada uno solo usa su propia `BASE_URL`/puerto para construir la redirect URI.
- NestJS y Express usan `openid-client`, que valida `client_id` no vacio al **construir** el cliente (no solo al hacer login). Por eso sus `.env` usan un placeholder ("unset") cuando una nube no esta configurada, en vez de dejar el valor vacio. Python y PHP no tienen ese problema: construyen la peticion al vuelo en cada request.
