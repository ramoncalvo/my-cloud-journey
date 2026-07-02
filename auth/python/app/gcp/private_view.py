from fastapi import Request
from fastapi.templating import Jinja2Templates
from starlette.responses import RedirectResponse

from app.shared.session import get_user


async def handle(request: Request, templates: Jinja2Templates):
    user = get_user(request, "gcp")
    if user is None:
        return RedirectResponse("/auth/gcp/login")
    return templates.TemplateResponse(
        request, "private.html", {"cloud": "gcp", "cloud_label": "Google Cloud", "user": user}
    )
