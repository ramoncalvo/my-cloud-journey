<?php

function azure_config(): array
{
    $tenant = env("AZURE_TENANT_ID", "common");
    return [
        "client_id" => env("AZURE_CLIENT_ID"),
        "client_secret" => env("AZURE_CLIENT_SECRET"),
        "authorize_endpoint" => "https://login.microsoftonline.com/$tenant/oauth2/v2.0/authorize",
        "token_endpoint" => "https://login.microsoftonline.com/$tenant/oauth2/v2.0/token",
        "userinfo_endpoint" => "https://graph.microsoft.com/oidc/userinfo",
        "redirect_uri" => env("BASE_URL", "http://localhost:8006") . "/auth/azure/callback",
    ];
}
