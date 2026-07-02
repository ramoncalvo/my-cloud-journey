from fastapi import Request
from starlette.responses import RedirectResponse

from app.aws.client import get_client
from app.shared.session import set_user


async def handle(request: Request) -> RedirectResponse:
    client = get_client()
    token = await client.authorize_access_token(request)
    # Cognito no siempre incluye claims completas en el id_token; se pide
    # el userinfo endpoint directamente con el access_token.
    resp = await client.get("oauth2/userInfo", token=token)
    set_user(request, "aws", resp.json())
    return RedirectResponse("/aws/private")
