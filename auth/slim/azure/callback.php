<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

function azure_callback(Request $request, Response $response): Response
{
    $params = $request->getQueryParams();
    if (($params["state"] ?? null) !== ($_SESSION["state_azure"] ?? null)) {
        $response->getBody()->write(layout("<pre>state invalido</pre>"));
        return $response->withStatus(400);
    }

    $provider = azure_provider();
    try {
        $token = $provider->getAccessToken("authorization_code", ["code" => $params["code"] ?? ""]);
        $_SESSION["user_azure"] = $provider->getResourceOwner($token)->toArray();
        return $response->withHeader("Location", "/azure/private")->withStatus(302);
    } catch (\Throwable $e) {
        $response->getBody()->write(layout("<pre>" . htmlspecialchars($e->getMessage()) . "</pre>"));
        return $response->withStatus(500);
    }
}
