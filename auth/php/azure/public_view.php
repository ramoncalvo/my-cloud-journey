<?php

function azure_public_view(): void
{
    $label = "Azure";
    $user = $_SESSION["user_azure"] ?? null;
    if ($user) {
        $email = $user["email"] ?? $user["username"] ?? "usuario";
        $body = "<p>Sesion iniciada en $label como <strong>" . htmlspecialchars($email) . "</strong>.</p>
            <a class=\"btn\" href=\"/azure/private\">Vista privada</a>
            <a class=\"btn\" href=\"/auth/azure/logout\">Cerrar sesion</a>";
    } else {
        $body = "<p>Esta pagina es publica, no requiere autenticacion.</p>
            <a class=\"btn\" href=\"/auth/azure/login\">Iniciar sesion con $label</a>";
    }
    echo layout("<h1>$label</h1>$body");
}
