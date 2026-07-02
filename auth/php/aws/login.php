<?php

function aws_login(): void
{
    $config = aws_config();
    $state = bin2hex(random_bytes(16));
    $_SESSION["state_aws"] = $state;
    $query = http_build_query([
        "client_id" => $config["client_id"],
        "redirect_uri" => $config["redirect_uri"],
        "response_type" => "code",
        "scope" => "openid email profile",
        "state" => $state,
    ]);
    redirectTo($config["authorize_endpoint"] . "?" . $query);
}
