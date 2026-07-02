from pathlib import Path

from fastapi import FastAPI, Request
from fastapi.templating import Jinja2Templates
from starlette.middleware.sessions import SessionMiddleware

from app import config
from app.aws import callback as aws_callback
from app.aws import login as aws_login
from app.aws import logout as aws_logout
from app.aws import private_view as aws_private_view
from app.aws import public_view as aws_public_view
from app.azure import callback as azure_callback
from app.azure import login as azure_login
from app.azure import logout as azure_logout
from app.azure import private_view as azure_private_view
from app.azure import public_view as azure_public_view
from app.gcp import callback as gcp_callback
from app.gcp import login as gcp_login
from app.gcp import logout as gcp_logout
from app.gcp import private_view as gcp_private_view
from app.gcp import public_view as gcp_public_view

BASE_DIR = Path(__file__).resolve().parent

app = FastAPI(title="Multi-cloud SSO Lab")

# SessionMiddleware guarda la sesion en una cookie firmada (no en servidor),
# usando SESSION_SECRET_KEY para firmar. Gracias a esto request.session
# funciona como un dict persistente entre peticiones del mismo navegador.
app.add_middleware(SessionMiddleware, secret_key=config.SESSION_SECRET_KEY)

templates = Jinja2Templates(directory=str(BASE_DIR / "templates"))

CLOUD_LABELS = {"aws": "AWS", "azure": "Azure", "gcp": "Google Cloud"}


@app.get("/")
async def index(request: Request):
    return templates.TemplateResponse(request, "index.html", {"clouds": CLOUD_LABELS})


# Screaming architecture: cada ruta mapea 1:1 con un archivo de app/aws|azure|gcp/,
# sin un dispatcher generico "/{cloud}" de por medio. Cada funcion de aqui
# abajo es toda la conexion entre HTTP y esa "slice" de negocio; no llevan
# logica propia, solo delegan. (No se usan lambdas: FastAPI necesita la
# anotacion de tipo "request: Request" en la firma para saber que debe
# inyectar el objeto Request en vez de tratarlo como query param.)


@app.get("/aws")
async def aws_public(request: Request):
    return await aws_public_view.handle(request, templates)


@app.get("/aws/private")
async def aws_private(request: Request):
    return await aws_private_view.handle(request, templates)


app.get("/auth/aws/login")(aws_login.handle)
app.get("/auth/aws/callback")(aws_callback.handle)
app.get("/auth/aws/logout")(aws_logout.handle)


@app.get("/azure")
async def azure_public(request: Request):
    return await azure_public_view.handle(request, templates)


@app.get("/azure/private")
async def azure_private(request: Request):
    return await azure_private_view.handle(request, templates)


app.get("/auth/azure/login")(azure_login.handle)
app.get("/auth/azure/callback")(azure_callback.handle)
app.get("/auth/azure/logout")(azure_logout.handle)


@app.get("/gcp")
async def gcp_public(request: Request):
    return await gcp_public_view.handle(request, templates)


@app.get("/gcp/private")
async def gcp_private(request: Request):
    return await gcp_private_view.handle(request, templates)


app.get("/auth/gcp/login")(gcp_login.handle)
app.get("/auth/gcp/callback")(gcp_callback.handle)
app.get("/auth/gcp/logout")(gcp_logout.handle)
