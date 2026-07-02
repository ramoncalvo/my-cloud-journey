from fastapi import Request
from starlette.responses import RedirectResponse

from app.azure.client import get_client
from app.shared.session import set_user


async def handle(request: Request) -> RedirectResponse:
    client = get_client()
    token = await client.authorize_access_token(request)
    userinfo = token.get("userinfo") or await client.parse_id_token(request, token)
    set_user(request, "azure", dict(userinfo))
    return RedirectResponse("/azure/private")
