<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

function gcp_callback(Request $request, Response $response): Response
{
    $params = $request->getQueryParams();
    if (($params["state"] ?? null) !== ($_SESSION["state_gcp"] ?? null)) {
        $response->getBody()->write(layout("<pre>state invalido</pre>"));
        return $response->withStatus(400);
    }

    $provider = gcp_provider();
    try {
        $token = $provider->getAccessToken("authorization_code", ["code" => $params["code"] ?? ""]);
        $_SESSION["user_gcp"] = $provider->getResourceOwner($token)->toArray();
        return $response->withHeader("Location", "/gcp/private")->withStatus(302);
    } catch (\Throwable $e) {
        $response->getBody()->write(layout("<pre>" . htmlspecialchars($e->getMessage()) . "</pre>"));
        return $response->withStatus(500);
    }
}
