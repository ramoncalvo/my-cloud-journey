import os

from dotenv import load_dotenv

# Lee el archivo .env (si existe) y vuelca sus variables en os.environ,
# asi no hace falta exportarlas a mano en la terminal para desarrollo local.
load_dotenv()

# URL publica de esta app; se usa para construir las redirect_uri de OAuth
# (http://localhost:8000/auth/{cloud}/callback). En produccion seria el
# dominio real, y debe coincidir con lo registrado en cada proveedor SSO.
BASE_URL = os.environ.get("BASE_URL", "http://localhost:8000")
# Clave para firmar la cookie de sesion (SessionMiddleware). Si cambia,
# todas las sesiones activas se invalidan. En real nunca se hardcodea.
SESSION_SECRET_KEY = os.environ.get("SESSION_SECRET_KEY", "change-me")

# --- AWS Cognito: datos del User Pool y del App Client creados en la consola ---
AWS_COGNITO_REGION = os.environ.get("AWS_COGNITO_REGION", "")
AWS_COGNITO_USER_POOL_ID = os.environ.get("AWS_COGNITO_USER_POOL_ID", "")
AWS_COGNITO_CLIENT_ID = os.environ.get("AWS_COGNITO_CLIENT_ID", "")
AWS_COGNITO_CLIENT_SECRET = os.environ.get("AWS_COGNITO_CLIENT_SECRET", "")
# Dominio del Hosted UI (ej: https://tu-prefijo.auth.eu-west-1.amazoncognito.com),
# es donde viven realmente los endpoints de authorize/token/userinfo de Cognito.
AWS_COGNITO_DOMAIN = os.environ.get("AWS_COGNITO_DOMAIN", "")

# --- Azure AD / Microsoft Entra ID: datos del App Registration ---
AZURE_TENANT_ID = os.environ.get("AZURE_TENANT_ID", "")
AZURE_CLIENT_ID = os.environ.get("AZURE_CLIENT_ID", "")
AZURE_CLIENT_SECRET = os.environ.get("AZURE_CLIENT_SECRET", "")

# --- Google OAuth: credenciales del OAuth Client creado en Google Cloud Console ---
GOOGLE_CLIENT_ID = os.environ.get("GOOGLE_CLIENT_ID", "")
GOOGLE_CLIENT_SECRET = os.environ.get("GOOGLE_CLIENT_SECRET", "")
