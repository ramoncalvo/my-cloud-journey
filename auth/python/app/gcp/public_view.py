from fastapi import Request
from fastapi.templating import Jinja2Templates

from app.shared.session import get_user


async def handle(request: Request, templates: Jinja2Templates):
    user = get_user(request, "gcp")
    return templates.TemplateResponse(
        request, "public.html", {"cloud": "gcp", "cloud_label": "Google Cloud", "user": user}
    )
