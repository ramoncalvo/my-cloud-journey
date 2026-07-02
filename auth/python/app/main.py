from pathlib import Path

from fastapi import FastAPI, Request
from fastapi.responses import RedirectResponse
from fastapi.templating import Jinja2Templates
from starlette.middleware.sessions import SessionMiddleware

from app import auth, config

BASE_DIR = Path(__file__).resolve().parent

app = FastAPI(title="Multi-cloud SSO Lab")

# SessionMiddleware guarda la sesion en una cookie firmada (no en servidor),
# usando SESSION_SECRET_KEY para firmar. Gracias a esto request.session
# funciona como un dict persistente entre peticiones del mismo navegador.
app.add_middleware(SessionMiddleware, secret_key=config.SESSION_SECRET_KEY)

templates = Jinja2Templates(directory=str(BASE_DIR / "templates"))

# Nombre "bonito" de cada nube para mostrar en las plantillas.
CLOUD_LABELS = {"aws": "AWS", "azure": "Azure", "gcp": "Google Cloud"}


@app.get("/")
async def index(request: Request):
    # Pagina de entrada: enlaza a las 6 vistas (3 publicas + 3 privadas).
    return templates.TemplateResponse(
        request, "index.html", {"clouds": CLOUD_LABELS}
    )


@app.get("/{cloud}")
async def public_view(request: Request, cloud: str):
    """Vista publica de una nube: no exige login. Si el usuario ya tiene
    sesion iniciada en esa nube, se lo mostramos igualmente (para poder
    enlazar a la vista privada o cerrar sesion desde aqui)."""
    if cloud not in CLOUD_LABELS:
        return RedirectResponse("/")
    user = auth.get_user(request, cloud)
    return templates.TemplateResponse(
        request,
        "public.html",
        {"cloud": cloud, "cloud_label": CLOUD_LABELS[cloud], "user": user},
    )


@app.get("/{cloud}/private")
async def private_view(request: Request, cloud: str):
    """Vista privada: aqui esta el control de acceso real. Si no hay
    sesion para esta nube, redirigimos al login SSO de esa nube en vez
    de devolver un 401/403, para que el flujo sea "clic y te loguea"."""
    if cloud not in CLOUD_LABELS:
        return RedirectResponse("/")
    user = auth.get_user(request, cloud)
    if user is None:
        return RedirectResponse(f"/auth/{cloud}/login")
    return templates.TemplateResponse(
        request,
        "private.html",
        {"cloud": cloud, "cloud_label": CLOUD_LABELS[cloud], "user": user},
    )


@app.get("/auth/{cloud}/login")
async def login(request: Request, cloud: str):
    # Delega en auth.login: arma la URL de autorizacion del proveedor
    # (con client_id, redirect_uri, scope, state...) y redirige alli.
    return await auth.login(request, cloud)


@app.get("/auth/{cloud}/callback")
async def callback(request: Request, cloud: str):
    # El proveedor SSO redirige aqui tras el login del usuario. auth.callback
    # intercambia el "code" recibido por un token y guarda la identidad
    # en la sesion; luego mandamos al usuario a su vista privada.
    await auth.callback(request, cloud)
    return RedirectResponse(f"/{cloud}/private")


@app.get("/auth/{cloud}/logout")
async def logout(request: Request, cloud: str):
    # Logout local (borra la sesion de esta app), no cierra sesion en
    # el proveedor. Volvemos a la vista publica de esa nube.
    auth.logout(request, cloud)
    return RedirectResponse(f"/{cloud}")
