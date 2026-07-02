<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

function gcp_logout(Request $request, Response $response): Response
{
    unset($_SESSION["user_gcp"]);
    return $response->withHeader("Location", "/gcp")->withStatus(302);
}
