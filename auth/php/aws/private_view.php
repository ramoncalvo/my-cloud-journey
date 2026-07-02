<?php

function aws_private_view(): void
{
    $user = $_SESSION["user_aws"] ?? null;
    if (!$user) {
        redirectTo("/auth/aws/login");
    }
    $body = "<pre>" . htmlspecialchars(json_encode($user, JSON_PRETTY_PRINT)) . "</pre>
        <a class=\"btn\" href=\"/auth/aws/logout\">Cerrar sesion</a>";
    echo layout("<h1>AWS</h1>$body");
}
