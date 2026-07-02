<?php

require __DIR__ . "/../vendor/autoload.php";

session_start();

require __DIR__ . "/../shared/env.php";
require __DIR__ . "/../shared/layout.php";

require __DIR__ . "/../aws/client.php";
require __DIR__ . "/../aws/login.php";
require __DIR__ . "/../aws/callback.php";
require __DIR__ . "/../aws/logout.php";
require __DIR__ . "/../aws/public_view.php";
require __DIR__ . "/../aws/private_view.php";

require __DIR__ . "/../azure/client.php";
require __DIR__ . "/../azure/login.php";
require __DIR__ . "/../azure/callback.php";
require __DIR__ . "/../azure/logout.php";
require __DIR__ . "/../azure/public_view.php";
require __DIR__ . "/../azure/private_view.php";

require __DIR__ . "/../gcp/client.php";
require __DIR__ . "/../gcp/login.php";
require __DIR__ . "/../gcp/callback.php";
require __DIR__ . "/../gcp/logout.php";
require __DIR__ . "/../gcp/public_view.php";
require __DIR__ . "/../gcp/private_view.php";

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

$app = AppFactory::create();

$app->get("/", function (Request $request, Response $response) {
    $response->getBody()->write(layout('
        <p>Version Slim del lab. Cada nube tiene vista publica y privada por SSO (OIDC).</p>
        <div class="card"><h2>AWS</h2><a href="/aws">Vista publica</a> &middot; <a href="/aws/private">Vista privada</a></div>
        <div class="card"><h2>Azure</h2><a href="/azure">Vista publica</a> &middot; <a href="/azure/private">Vista privada</a></div>
        <div class="card"><h2>Google Cloud</h2><a href="/gcp">Vista publica</a> &middot; <a href="/gcp/private">Vista privada</a></div>
    '));
    return $response;
});

// Screaming architecture: cada ruta mapea 1:1 con una funcion de
// aws|azure|gcp/*.php. index.php es solo el front controller de Slim,
// no tiene logica de negocio propia.
$app->get("/aws", "aws_public_view");
$app->get("/aws/private", "aws_private_view");
$app->get("/auth/aws/login", "aws_login");
$app->get("/auth/aws/callback", "aws_callback");
$app->get("/auth/aws/logout", "aws_logout");

$app->get("/azure", "azure_public_view");
$app->get("/azure/private", "azure_private_view");
$app->get("/auth/azure/login", "azure_login");
$app->get("/auth/azure/callback", "azure_callback");
$app->get("/auth/azure/logout", "azure_logout");

$app->get("/gcp", "gcp_public_view");
$app->get("/gcp/private", "gcp_private_view");
$app->get("/auth/gcp/login", "gcp_login");
$app->get("/auth/gcp/callback", "gcp_callback");
$app->get("/auth/gcp/logout", "gcp_logout");

$app->run();
