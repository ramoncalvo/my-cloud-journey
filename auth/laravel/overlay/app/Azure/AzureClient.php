<?php

namespace App\Azure;

use League\OAuth2\Client\Provider\GenericProvider;

class AzureClient
{
    public static function provider(): GenericProvider
    {
        $tenant = env("AZURE_TENANT_ID", "common");
        return new GenericProvider([
            "clientId" => env("AZURE_CLIENT_ID"),
            "clientSecret" => env("AZURE_CLIENT_SECRET"),
            "redirectUri" => env("BASE_URL", "http://localhost:8008") . "/auth/azure/callback",
            "urlAuthorize" => "https://login.microsoftonline.com/$tenant/oauth2/v2.0/authorize",
            "urlAccessToken" => "https://login.microsoftonline.com/$tenant/oauth2/v2.0/token",
            "urlResourceOwnerDetails" => "https://graph.microsoft.com/oidc/userinfo",
        ]);
    }
}
