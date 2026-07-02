<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

function aws_login(Request $request, Response $response): Response
{
    $provider = aws_provider();
    $authUrl = $provider->getAuthorizationUrl(["scope" => "openid email profile"]);
    $_SESSION["state_aws"] = $provider->getState();
    return $response->withHeader("Location", $authUrl)->withStatus(302);
}
