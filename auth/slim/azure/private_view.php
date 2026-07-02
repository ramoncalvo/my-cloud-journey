<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

function azure_private_view(Request $request, Response $response): Response
{
    $user = $_SESSION["user_azure"] ?? null;
    if (!$user) {
        return $response->withHeader("Location", "/auth/azure/login")->withStatus(302);
    }
    $body = "<pre>" . htmlspecialchars(json_encode($user, JSON_PRETTY_PRINT)) . "</pre>
        <a class=\"btn\" href=\"/auth/azure/logout\">Cerrar sesion</a>";
    $response->getBody()->write(layout("<h1>Azure</h1>$body"));
    return $response;
}
