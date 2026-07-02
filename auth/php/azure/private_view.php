<?php

function azure_private_view(): void
{
    $user = $_SESSION["user_azure"] ?? null;
    if (!$user) {
        redirectTo("/auth/azure/login");
    }
    $body = "<pre>" . htmlspecialchars(json_encode($user, JSON_PRETTY_PRINT)) . "</pre>
        <a class=\"btn\" href=\"/auth/azure/logout\">Cerrar sesion</a>";
    echo layout("<h1>Azure</h1>$body");
}
