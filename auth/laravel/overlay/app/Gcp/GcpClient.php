<?php

namespace App\Gcp;

use League\OAuth2\Client\Provider\GenericProvider;

class GcpClient
{
    public static function provider(): GenericProvider
    {
        return new GenericProvider([
            "clientId" => env("GOOGLE_CLIENT_ID"),
            "clientSecret" => env("GOOGLE_CLIENT_SECRET"),
            "redirectUri" => env("BASE_URL", "http://localhost:8008") . "/auth/gcp/callback",
            "urlAuthorize" => "https://accounts.google.com/o/oauth2/v2/auth",
            "urlAccessToken" => "https://oauth2.googleapis.com/token",
            "urlResourceOwnerDetails" => "https://openidconnect.googleapis.com/v1/userinfo",
        ]);
    }
}
