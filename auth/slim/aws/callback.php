<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

function aws_callback(Request $request, Response $response): Response
{
    $params = $request->getQueryParams();
    if (($params["state"] ?? null) !== ($_SESSION["state_aws"] ?? null)) {
        $response->getBody()->write(layout("<pre>state invalido</pre>"));
        return $response->withStatus(400);
    }

    $provider = aws_provider();
    try {
        $token = $provider->getAccessToken("authorization_code", ["code" => $params["code"] ?? ""]);
        // Igual que en las otras implementaciones: se pide el resource
        // owner (userinfo endpoint) en vez de decodificar el id_token.
        $_SESSION["user_aws"] = $provider->getResourceOwner($token)->toArray();
        return $response->withHeader("Location", "/aws/private")->withStatus(302);
    } catch (\Throwable $e) {
        $response->getBody()->write(layout("<pre>" . htmlspecialchars($e->getMessage()) . "</pre>"));
        return $response->withStatus(500);
    }
}
