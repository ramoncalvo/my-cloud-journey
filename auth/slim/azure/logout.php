<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

function azure_logout(Request $request, Response $response): Response
{
    unset($_SESSION["user_azure"]);
    return $response->withHeader("Location", "/azure")->withStatus(302);
}
