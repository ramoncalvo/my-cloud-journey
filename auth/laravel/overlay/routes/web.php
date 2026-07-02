<?php

use App\Aws\AwsController;
use App\Azure\AzureController;
use App\Gcp\GcpController;
use App\Shared\Layout;
use Illuminate\Support\Facades\Route;

Route::get("/", function () {
    return response(Layout::render('
        <p>Version Laravel del lab. Cada nube tiene vista publica y privada por SSO (OIDC).</p>
        <div class="card"><h2>AWS</h2><a href="/aws">Vista publica</a> &middot; <a href="/aws/private">Vista privada</a></div>
        <div class="card"><h2>Azure</h2><a href="/azure">Vista publica</a> &middot; <a href="/azure/private">Vista privada</a></div>
        <div class="card"><h2>Google Cloud</h2><a href="/gcp">Vista publica</a> &middot; <a href="/gcp/private">Vista privada</a></div>
    '));
});

// Screaming architecture: cada ruta mapea 1:1 con un metodo del
// controller de esa nube (app/Aws, app/Azure, app/Gcp). routes/web.php
// no tiene logica de negocio, solo el cableado HTTP.
Route::get("/aws", [AwsController::class, "publicView"]);
Route::get("/aws/private", [AwsController::class, "privateView"]);
Route::get("/auth/aws/login", [AwsController::class, "login"]);
Route::get("/auth/aws/callback", [AwsController::class, "callback"]);
Route::get("/auth/aws/logout", [AwsController::class, "logout"]);

Route::get("/azure", [AzureController::class, "publicView"]);
Route::get("/azure/private", [AzureController::class, "privateView"]);
Route::get("/auth/azure/login", [AzureController::class, "login"]);
Route::get("/auth/azure/callback", [AzureController::class, "callback"]);
Route::get("/auth/azure/logout", [AzureController::class, "logout"]);

Route::get("/gcp", [GcpController::class, "publicView"]);
Route::get("/gcp/private", [GcpController::class, "privateView"]);
Route::get("/auth/gcp/login", [GcpController::class, "login"]);
Route::get("/auth/gcp/callback", [GcpController::class, "callback"]);
Route::get("/auth/gcp/logout", [GcpController::class, "logout"]);
