from authlib.integrations.starlette_client import OAuth

from app import config

# Google soporta descubrimiento OIDC estandar: con server_metadata_url,
# Authlib resuelve solo authorize/token/jwks.
_oauth = OAuth()
_oauth.register(
    name="gcp",
    client_id=config.GOOGLE_CLIENT_ID,
    client_secret=config.GOOGLE_CLIENT_SECRET,
    server_metadata_url="https://accounts.google.com/.well-known/openid-configuration",
    client_kwargs={"scope": "openid email profile"},
)


def get_client():
    return _oauth.create_client("gcp")
