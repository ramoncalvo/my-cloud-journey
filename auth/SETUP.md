# Setup del lab de SSO multi-cloud

El mismo lab implementado en 7 stacks para comparar frameworks, todos con
el SSO (OIDC) real de cada nube:

| Stack | Carpeta | Puerto |
|---|---|---|
| Python (FastAPI + Authlib) | `python/` | 8000 |
| C# (ASP.NET Core minimal API) | `csharp/` | 8001 |
| Java (Spring Boot + Spring Security OAuth2 Client) | `springboot/` | 8002 |
| Node (NestJS + openid-client) | `nestjs/` | 8003 |
| Node (Express + openid-client) | `express/` | 8004 |
| Go vanilla (net/http + golang.org/x/oauth2) | `go/` | 8005 |
| PHP vanilla (servidor embebido + curl) | `php/` | 8006 |

Cada uno expone 3 vistas publicas (`/aws`, `/azure`, `/gcp`) y 3 privadas
(`/aws/private`, `/azure/private`, `/gcp/private`). Necesitas cuenta
gratuita en las 3 nubes para registrar la app — no hay coste, pero no es
un simulador: son registros reales de aplicaciones OAuth/OIDC, compartidos
por los 7 stacks (mismas credenciales, distintas redirect URIs por puerto).

Las apps de Cognito y Azure AD se pueden crear/destruir con un comando via
Terraform — ver `../terraform/README.md`. Google no tiene equivalente por
Terraform (ver esa misma nota) y se configura a mano, seccion 5 de aqui.

## 1. Requisitos previos

- Docker + Docker Compose (los 7 stacks corren en contenedor, no hace falta Python/.NET/Java/Node/Go/PHP instalados en local)
- Una cuenta AWS (free tier, requiere tarjeta solo para verificacion de identidad)
- Una cuenta Microsoft/Azure (puedes usar el tenant gratuito que se crea al abrir un Azure free trial, o un tenant de Microsoft Entra ID gratuito sin trial)
- Una cuenta de Google + proyecto en Google Cloud Console (gratis, la creacion de credenciales OAuth no requiere billing activado)

## 2. Redirect URIs por stack

Todos usan `/auth/{cloud}/callback`, salvo Spring Boot que usa la
convencion propia de Spring Security (`/login/oauth2/code/{registrationId}`).
Hay que dar de alta las 7 URLs de cada nube en el proveedor correspondiente
(o dejar que Terraform lo haga por ti para AWS/Azure, ver arriba):

| Nube | Python (8000) | C# (8001) | Spring Boot (8002) | NestJS (8003) | Express (8004) | Go (8005) | PHP (8006) |
|---|---|---|---|---|---|---|---|
| AWS Cognito | `.../auth/aws/callback` | `.../auth/aws/callback` | `.../login/oauth2/code/aws` | `.../auth/aws/callback` | `.../auth/aws/callback` | `.../auth/aws/callback` | `.../auth/aws/callback` |
| Azure AD | `.../auth/azure/callback` | `.../auth/azure/callback` | `.../login/oauth2/code/azure` | `.../auth/azure/callback` | `.../auth/azure/callback` | `.../auth/azure/callback` | `.../auth/azure/callback` |
| Google | `.../auth/gcp/callback` | `.../auth/gcp/callback` | `.../login/oauth2/code/google` | `.../auth/gcp/callback` | `.../auth/gcp/callback` | `.../auth/gcp/callback` | `.../auth/gcp/callback` |

(sustituye `...` por `http://localhost:PUERTO` segun la columna, ej.
`http://localhost:8006/auth/aws/callback` para PHP).

## 3. AWS Cognito (SSO para `/aws`)

**Opcion rapida:** `cd ../terraform && make apply && make outputs` crea el
User Pool + App Client con las 7 callback URLs ya configuradas.

**Manual:**

1. Consola AWS -> **Cognito** -> **User pools** -> **Create user pool**.
2. Sign-in options: email. El resto puedes dejarlo por defecto.
3. En **App integration**:
   - Crea un **dominio de Cognito** (Hosted UI), ej: `tu-prefijo.auth.eu-west-1.amazoncognito.com`.
   - Crea un **App client** (tipo confidencial, con client secret).
   - En el App client, configura:
     - **Allowed callback URLs**: las 7 URLs de AWS Cognito de la tabla anterior (una por linea).
     - **Allowed sign-out URLs**: `http://localhost:PUERTO/aws` para cada uno de los 7 puertos.
     - **OAuth grant types**: Authorization code grant
     - **OpenID scopes**: `openid`, `email`, `profile`
4. Anota: region, User pool ID, Client ID, Client secret y el dominio del Hosted UI.
5. Rellena en `.env` (en `auth/`, compartido por los 7 stacks):
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
2. Redirect URIs (tipo **Web**): agrega las 7 URLs de Azure AD de la tabla anterior.
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
4. **Authorized redirect URIs**: agrega las 7 URLs de Google de la tabla anterior.
5. Anota **Client ID** y **Client secret**.
6. Rellena en `.env`:
   ```
   GOOGLE_CLIENT_ID=...
   GOOGLE_CLIENT_SECRET=...
   ```

## 6. Ejecutar los 7 stacks

Todo lo de este lab vive bajo `auth/`, ejecuta los comandos desde ahi:

```bash
cd auth
cp .env.example .env   # si no existe ya; rellena los valores de las 3 nubes
python3 -c "import secrets; print(secrets.token_hex(32))"  # pega el resultado en SESSION_SECRET_KEY

make up      # docker compose build + up -d de los 7 stacks
```

- `http://localhost:8000` — Python (FastAPI)
- `http://localhost:8001` — C# (ASP.NET Core)
- `http://localhost:8002` — Spring Boot
- `http://localhost:8003` — NestJS
- `http://localhost:8004` — Express
- `http://localhost:8005` — Go vanilla
- `http://localhost:8006` — PHP vanilla

En cada una: `/aws`, `/azure`, `/gcp` son publicas; `/aws/private`,
`/azure/private`, `/gcp/private` piden login SSO contra su nube.

Para levantar/parar un stack suelto en vez de los 7, usa `STACK=`:
```bash
make up STACK=go
make down STACK=go
make logs STACK=go
```

Otros comandos: `make down`, `make restart`, `make status`, `make logs`.

## Notas

- Sin rellenar las credenciales de una nube, su vista publica funciona pero el login de esa nube fallara (el proveedor rechazara un client_id vacio/invalido). Puedes configurar las nubes una a una.
- Nada de esto tiene coste: Cognito, App registrations de Entra ID y credenciales OAuth de Google son gratuitas de crear y usar en bajo volumen.
- Los 7 stacks comparten el mismo `.env`: mismas credenciales de cada nube, cada uno solo usa su propia `BASE_URL`/puerto para construir la redirect URI.
- Varios frameworks (Spring Boot, ASP.NET Core, Express, NestJS) validan la configuracion OAuth2 de forma "eager" (al arrancar o en cada request), a diferencia de Python/Go/PHP que solo fallan al intentar el login. Por eso sus `.env` usan un placeholder ("unset") cuando una nube no esta configurada, en vez de dejar el valor vacio.
