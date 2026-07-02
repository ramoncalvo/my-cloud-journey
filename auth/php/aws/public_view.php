<?php

function aws_public_view(): void
{
    $label = "AWS";
    $user = $_SESSION["user_aws"] ?? null;
    if ($user) {
        $email = $user["email"] ?? $user["username"] ?? "usuario";
        $body = "<p>Sesion iniciada en $label como <strong>" . htmlspecialchars($email) . "</strong>.</p>
            <a class=\"btn\" href=\"/aws/private\">Vista privada</a>
            <a class=\"btn\" href=\"/auth/aws/logout\">Cerrar sesion</a>";
    } else {
        $body = "<p>Esta pagina es publica, no requiere autenticacion.</p>
            <a class=\"btn\" href=\"/auth/aws/login\">Iniciar sesion con $label</a>";
    }
    echo layout("<h1>$label</h1>$body");
}
