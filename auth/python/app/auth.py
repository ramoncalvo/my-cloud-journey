from authlib.integrations.starlette_client import OAuth
from fastapi import HTTPException, Request
from starlette.responses import RedirectResponse

from app import config

# --- Glosario de siglas usadas en este archivo ---
# SSO   Single Sign-On: iniciar sesion una vez y quedar autenticado en varios
#       sistemas, sin volver a meter usuario/contrasena en cada uno.
# OAuth2 Open Authorization 2.0: protocolo estandar para que una app obtenga
#       acceso limitado (un token) a recursos de un usuario, sin conocer su
#       contrasena. Resuelve "autorizacion" (que puede hacer la app).
# OIDC  OpenID Connect: capa de identidad construida ENCIMA de OAuth2. Anade
#       el concepto de id_token para resolver "autenticacion" (quien es el
#       usuario), ademas del access_token de OAuth2.
# URI/URL Uniform Resource Identifier/Locator: la direccion (ej. una URL web).
# JWT   JSON Web Token: un token con formato estandar (cabecera.payload.firma)
#       codificado en base64, usado tanto para id_token como a veces access_token.
# JWKS  JSON Web Key Set: el "llavero" de claves publicas que publica el
#       proveedor de identidad, usado para verificar la firma de un JWT.

# OAuth() es el "registro" de Authlib: aqui se dan de alta los proveedores
# (Google, Azure, AWS) para luego pedirle un cliente concreto a cada uno.
oauth = OAuth()

# --- Registro de proveedores OIDC (OpenID Connect) ---
# Cada proveedor publica un "documento de descubrimiento" en la URL
# .well-known/openid-configuration con las URLs de authorize, token,
# userinfo y las claves publicas JWKS (JSON Web Key Set) para verificar el
# id_token (JSON Web Token, JWT). Authlib puede leer ese documento solo si
# le pasamos server_metadata_url; si no, hay que dar las URLs a mano (ver Cognito).

# Google soporta descubrimiento OIDC estandar: con server_metadata_url,
# Authlib obtiene solo automaticamente authorize_url, token_url, jwks_uri, etc.
oauth.register(
    name="google",
    client_id=config.GOOGLE_CLIENT_ID,
    client_secret=config.GOOGLE_CLIENT_SECRET,
    server_metadata_url="https://accounts.google.com/.well-known/openid-configuration",
    # "scope" pide los datos que queremos en el token: identidad (openid),
    # email y perfil basico (nombre, foto).
    client_kwargs={"scope": "openid email profile"},
)

# Azure AD (Microsoft Entra ID) tambien soporta descubrimiento OIDC v2.0.
# El tenant_id en la URL identifica "que Azure AD" (que organizacion) emite
# los tokens; por eso hace falta saber el tenant antes de poder descubrir
# sus endpoints.
oauth.register(
    name="azure",
    client_id=config.AZURE_CLIENT_ID,
    client_secret=config.AZURE_CLIENT_SECRET,
    server_metadata_url=(
        f"https://login.microsoftonline.com/{config.AZURE_TENANT_ID}"
        "/v2.0/.well-known/openid-configuration"
    ),
    client_kwargs={"scope": "openid email profile"},
)

# El documento OIDC de Cognito (cognito-idp.{region}.amazonaws.com/{pool}/...)
# solo sirve para validar el id_token (issuer, jwks_uri), pero NO incluye
# authorization_endpoint ni token_endpoint: esos viven bajo el dominio propio
# del Hosted UI (el que configuras en la consola de Cognito), asi que aqui
# no usamos server_metadata_url sino que damos cada URL a mano.
oauth.register(
    name="aws",
    client_id=config.AWS_COGNITO_CLIENT_ID,
    client_secret=config.AWS_COGNITO_CLIENT_SECRET,
    access_token_url=f"{config.AWS_COGNITO_DOMAIN}/oauth2/token",
    authorize_url=f"{config.AWS_COGNITO_DOMAIN}/oauth2/authorize",
    userinfo_endpoint=f"{config.AWS_COGNITO_DOMAIN}/oauth2/userInfo",
    client_kwargs={"scope": "openid email profile"},
)

# Nombres de nube que expone la app (usados en las URLs: /aws, /azure, /gcp).
CLOUDS = {"aws", "azure", "gcp"}
# El provider registrado arriba para Google se llama "google" en Authlib,
# pero en las rutas de la app la nube se llama "gcp"; esta tabla traduce
# entre el nombre "de negocio" (cloud) y el nombre "tecnico" (provider).
PROVIDER_NAME = {"aws": "aws", "azure": "azure", "gcp": "google"}


def _session_key(cloud: str) -> str:
    # Cada nube guarda su propia sesion (user_aws, user_azure, user_gcp),
    # asi puedes estar logueado en unas nubes y no en otras a la vez.
    return f"user_{cloud}"


def get_user(request: Request, cloud: str) -> dict | None:
    # SessionMiddleware (ver main.py) guarda la sesion en una cookie firmada;
    # request.session es un dict normal que persiste entre peticiones.
    return request.session.get(_session_key(cloud))


async def login(request: Request, cloud: str) -> RedirectResponse:
    """Paso 1 del flujo OAuth2 (Open Authorization 2.0) Authorization Code:
    redirigir al usuario a la pantalla de login del proveedor (Cognito
    Hosted UI, login de Microsoft o de Google), pidiendole que autorice
    a esta app."""
    if cloud not in CLOUDS:
        raise HTTPException(status_code=404, detail="Nube desconocida")
    client = oauth.create_client(PROVIDER_NAME[cloud])
    # redirect_uri (Redirect URI, la URL de retorno) es donde el proveedor
    # devolvera al usuario tras el login, con un "code" de un solo uso en
    # la query string. Debe coincidir exactamente con la URL registrada
    # en la app SSO (Single Sign-On) de cada nube.
    redirect_uri = f"{config.BASE_URL}/auth/{cloud}/callback"
    return await client.authorize_redirect(request, redirect_uri)


async def callback(request: Request, cloud: str) -> dict:
    """Paso 2 del flujo: el proveedor redirige aqui con un "code". Authlib
    intercambia ese code por un access_token (y normalmente un id_token)
    llamando al token_endpoint del proveedor. El id_token es un JWT (JSON
    Web Token) firmado que contiene las claims (datos) de identidad:
    sub (subject, el ID unico del usuario), email, name..."""
    if cloud not in CLOUDS:
        raise HTTPException(status_code=404, detail="Nube desconocida")
    client = oauth.create_client(PROVIDER_NAME[cloud])
    token = await client.authorize_access_token(request)

    if cloud == "aws":
        # Cognito no siempre incluye id_token parseable con las claims esperadas
        # en todas las configuraciones, se pide el userinfo endpoint directamente
        # (otra llamada HTTP autenticada con el access_token) en vez de
        # confiar en el contenido del id_token.
        resp = await client.get("oauth2/userInfo", token=token)
        userinfo = resp.json()
    else:
        # Google/Azure: Authlib ya valida la firma del id_token contra el
        # JWKS (JSON Web Key Set, las claves publicas del proveedor) y
        # devuelve las claims como dict en "userinfo".
        userinfo = token.get("userinfo") or await client.parse_id_token(request, token)

    # Guardamos solo la info de identidad en la sesion, no el access_token:
    # para este lab no necesitamos llamar a APIs del proveedor en nombre
    # del usuario, solo saber "quien es" (autenticacion, no autorizacion).
    request.session[_session_key(cloud)] = dict(userinfo)
    return userinfo


def logout(request: Request, cloud: str) -> None:
    # Esto es un "logout local": borra la sesion en esta app. El usuario
    # puede seguir teniendo sesion abierta en Cognito/Azure/Google mismos
    # (logout federado real requeriria redirigir al end_session_endpoint
    # de cada proveedor).
    request.session.pop(_session_key(cloud), None)
