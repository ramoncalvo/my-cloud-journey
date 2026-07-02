<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

function aws_private_view(Request $request, Response $response): Response
{
    $user = $_SESSION["user_aws"] ?? null;
    if (!$user) {
        return $response->withHeader("Location", "/auth/aws/login")->withStatus(302);
    }
    $body = "<pre>" . htmlspecialchars(json_encode($user, JSON_PRETTY_PRINT)) . "</pre>
        <a class=\"btn\" href=\"/auth/aws/logout\">Cerrar sesion</a>";
    $response->getBody()->write(layout("<h1>AWS</h1>$body"));
    return $response;
}
