<?php

// El documento OIDC de Cognito no expone authorization_endpoint ni
// token_endpoint (viven bajo el dominio del Hosted UI), asi que se
// configuran a mano en vez de un discovery document.
function aws_config(): array
{
    $domain = env("AWS_COGNITO_DOMAIN");
    return [
        "client_id" => env("AWS_COGNITO_CLIENT_ID"),
        "client_secret" => env("AWS_COGNITO_CLIENT_SECRET"),
        "authorize_endpoint" => "$domain/oauth2/authorize",
        "token_endpoint" => "$domain/oauth2/token",
        "userinfo_endpoint" => "$domain/oauth2/userInfo",
        "redirect_uri" => env("BASE_URL", "http://localhost:8006") . "/auth/aws/callback",
    ];
}
