from fastapi import Request
from fastapi.templating import Jinja2Templates

from app.shared.session import get_user


async def handle(request: Request, templates: Jinja2Templates):
    user = get_user(request, "azure")
    return templates.TemplateResponse(
        request, "public.html", {"cloud": "azure", "cloud_label": "Azure", "user": user}
    )
