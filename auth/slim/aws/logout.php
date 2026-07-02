<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

function aws_logout(Request $request, Response $response): Response
{
    unset($_SESSION["user_aws"]);
    return $response->withHeader("Location", "/aws")->withStatus(302);
}
