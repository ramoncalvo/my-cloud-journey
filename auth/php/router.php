<?php

// Version PHP vanilla del lab. Sin framework ni Composer: solo el
// servidor embebido de PHP + curl para las llamadas HTTP al proveedor.
//
// Screaming architecture: cada nube es su propia carpeta (aws/, azure/,
// gcp/) con sus 6 archivos (config, login, callback, logout,
// public_view, private_view). Este router.php es solo el front
// controller obligatorio del servidor embebido de PHP: no tiene logica
// de negocio, unicamente decide a que funcion de que carpeta llamar.

session_start();

require __DIR__ . "/shared/http.php";
require __DIR__ . "/shared/layout.php";

require __DIR__ . "/aws/config.php";
require __DIR__ . "/aws/login.php";
require __DIR__ . "/aws/callback.php";
require __DIR__ . "/aws/logout.php";
require __DIR__ . "/aws/public_view.php";
require __DIR__ . "/aws/private_view.php";

require __DIR__ . "/azure/config.php";
require __DIR__ . "/azure/login.php";
require __DIR__ . "/azure/callback.php";
require __DIR__ . "/azure/logout.php";
require __DIR__ . "/azure/public_view.php";
require __DIR__ . "/azure/private_view.php";

require __DIR__ . "/gcp/config.php";
require __DIR__ . "/gcp/login.php";
require __DIR__ . "/gcp/callback.php";
require __DIR__ . "/gcp/logout.php";
require __DIR__ . "/gcp/public_view.php";
require __DIR__ . "/gcp/private_view.php";

const ROUTES = [
    "GET /" => null, // manejado aparte, no tiene nube asociada
    "GET /aws" => "aws_public_view",
    "GET /aws/private" => "aws_private_view",
    "GET /auth/aws/login" => "aws_login",
    "GET /auth/aws/callback" => "aws_callback",
    "GET /auth/aws/logout" => "aws_logout",
    "GET /azure" => "azure_public_view",
    "GET /azure/private" => "azure_private_view",
    "GET /auth/azure/login" => "azure_login",
    "GET /auth/azure/callback" => "azure_callback",
    "GET /auth/azure/logout" => "azure_logout",
    "GET /gcp" => "gcp_public_view",
    "GET /gcp/private" => "gcp_private_view",
    "GET /auth/gcp/login" => "gcp_login",
    "GET /auth/gcp/callback" => "gcp_callback",
    "GET /auth/gcp/logout" => "gcp_logout",
];

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$key = "GET $path";

if ($path === "/") {
    echo layout('
        <p>Version PHP vanilla del lab. Cada nube tiene vista publica y privada por SSO (OIDC).</p>
        <div class="card"><h2>AWS</h2><a href="/aws">Vista publica</a> &middot; <a href="/aws/private">Vista privada</a></div>
        <div class="card"><h2>Azure</h2><a href="/azure">Vista publica</a> &middot; <a href="/azure/private">Vista privada</a></div>
        <div class="card"><h2>Google Cloud</h2><a href="/gcp">Vista publica</a> &middot; <a href="/gcp/private">Vista privada</a></div>
    ');
    exit;
}

if (isset(ROUTES[$key])) {
    ROUTES[$key]();
    exit;
}

redirectTo("/");
