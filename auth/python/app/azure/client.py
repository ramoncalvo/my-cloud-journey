from authlib.integrations.starlette_client import OAuth

from app import config

# Azure AD (Microsoft Entra ID) soporta descubrimiento OIDC estandar: con
# server_metadata_url, Authlib resuelve solo authorize/token/jwks.
_oauth = OAuth()
_oauth.register(
    name="azure",
    client_id=config.AZURE_CLIENT_ID,
    client_secret=config.AZURE_CLIENT_SECRET,
    server_metadata_url=(
        f"https://login.microsoftonline.com/{config.AZURE_TENANT_ID}"
        "/v2.0/.well-known/openid-configuration"
    ),
    client_kwargs={"scope": "openid email profile"},
)


def get_client():
    return _oauth.create_client("azure")
