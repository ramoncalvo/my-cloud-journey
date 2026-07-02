<?php

function gcp_private_view(): void
{
    $user = $_SESSION["user_gcp"] ?? null;
    if (!$user) {
        redirectTo("/auth/gcp/login");
    }
    $body = "<pre>" . htmlspecialchars(json_encode($user, JSON_PRETTY_PRINT)) . "</pre>
        <a class=\"btn\" href=\"/auth/gcp/logout\">Cerrar sesion</a>";
    echo layout("<h1>Google Cloud</h1>$body");
}
