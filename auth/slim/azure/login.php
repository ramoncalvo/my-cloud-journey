<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

function azure_login(Request $request, Response $response): Response
{
    $provider = azure_provider();
    $authUrl = $provider->getAuthorizationUrl(["scope" => "openid email profile"]);
    $_SESSION["state_azure"] = $provider->getState();
    return $response->withHeader("Location", $authUrl)->withStatus(302);
}
