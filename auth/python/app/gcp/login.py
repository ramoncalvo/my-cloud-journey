from fastapi import Request
from starlette.responses import RedirectResponse

from app import config
from app.gcp.client import get_client


async def handle(request: Request) -> RedirectResponse:
    redirect_uri = f"{config.BASE_URL}/auth/gcp/callback"
    return await get_client().authorize_redirect(request, redirect_uri)
