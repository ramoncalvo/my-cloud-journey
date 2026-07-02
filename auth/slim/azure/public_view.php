<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

function azure_public_view(Request $request, Response $response): Response
{
    $label = "Azure";
    $user = $_SESSION["user_azure"] ?? null;
    if ($user) {
        $email = $user["email"] ?? $user["username"] ?? "usuario";
        $body = "<p>Sesion iniciada en $label como <strong>" . htmlspecialchars($email) . "</strong>.</p>
            <a class=\"btn\" href=\"/azure/private\">Vista privada</a>
            <a class=\"btn\" href=\"/auth/azure/logout\">Cerrar sesion</a>";
    } else {
        $body = "<p>Esta pagina es publica, no requiere autenticacion.</p>
            <a class=\"btn\" href=\"/auth/azure/login\">Iniciar sesion con $label</a>";
    }
    $response->getBody()->write(layout("<h1>$label</h1>$body"));
    return $response;
}
