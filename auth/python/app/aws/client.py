from authlib.integrations.starlette_client import OAuth

from app import config

# El documento OIDC de Cognito no expone authorization_endpoint ni
# token_endpoint (viven bajo el dominio del Hosted UI), asi que se
# configuran a mano en vez de usar server_metadata_url.
_oauth = OAuth()
_oauth.register(
    name="aws",
    client_id=config.AWS_COGNITO_CLIENT_ID,
    client_secret=config.AWS_COGNITO_CLIENT_SECRET,
    access_token_url=f"{config.AWS_COGNITO_DOMAIN}/oauth2/token",
    authorize_url=f"{config.AWS_COGNITO_DOMAIN}/oauth2/authorize",
    userinfo_endpoint=f"{config.AWS_COGNITO_DOMAIN}/oauth2/userInfo",
    client_kwargs={"scope": "openid email profile"},
)


def get_client():
    return _oauth.create_client("aws")
