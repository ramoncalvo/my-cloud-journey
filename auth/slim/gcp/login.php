<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

function gcp_login(Request $request, Response $response): Response
{
    $provider = gcp_provider();
    $authUrl = $provider->getAuthorizationUrl(["scope" => "openid email profile"]);
    $_SESSION["state_gcp"] = $provider->getState();
    return $response->withHeader("Location", $authUrl)->withStatus(302);
}
