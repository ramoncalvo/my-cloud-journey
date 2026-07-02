from fastapi import Request
from starlette.responses import RedirectResponse

from app.shared.session import clear_user


def handle(request: Request) -> RedirectResponse:
    clear_user(request, "aws")
    return RedirectResponse("/aws")
