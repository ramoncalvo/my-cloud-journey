<?php

function gcp_public_view(): void
{
    $label = "Google Cloud";
    $user = $_SESSION["user_gcp"] ?? null;
    if ($user) {
        $email = $user["email"] ?? $user["username"] ?? "usuario";
        $body = "<p>Sesion iniciada en $label como <strong>" . htmlspecialchars($email) . "</strong>.</p>
            <a class=\"btn\" href=\"/gcp/private\">Vista privada</a>
            <a class=\"btn\" href=\"/auth/gcp/logout\">Cerrar sesion</a>";
    } else {
        $body = "<p>Esta pagina es publica, no requiere autenticacion.</p>
            <a class=\"btn\" href=\"/auth/gcp/login\">Iniciar sesion con $label</a>";
    }
    echo layout("<h1>$label</h1>$body");
}
