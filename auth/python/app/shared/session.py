from fastapi import Request

# Unica pieza compartida entre las 3 nubes: leer/guardar la sesion (cookie
# firmada por SessionMiddleware, ver main.py). No es logica de negocio de
# ninguna nube, por eso vive en shared/ y no dentro de aws/azure/gcp.


def get_user(request: Request, cloud: str) -> dict | None:
    return request.session.get(f"user_{cloud}")


def set_user(request: Request, cloud: str, claims: dict) -> None:
    request.session[f"user_{cloud}"] = claims


def clear_user(request: Request, cloud: str) -> None:
    request.session.pop(f"user_{cloud}", None)
