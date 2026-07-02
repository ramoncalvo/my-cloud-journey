<?php

namespace App\Aws;

use League\OAuth2\Client\Provider\GenericProvider;

class AwsClient
{
    // El documento OIDC de Cognito no expone authorization_endpoint ni
    // token_endpoint (viven bajo el dominio del Hosted UI), asi que se
    // configuran a mano en vez de un discovery document.
    public static function provider(): GenericProvider
    {
        $domain = env("AWS_COGNITO_DOMAIN");
        return new GenericProvider([
            "clientId" => env("AWS_COGNITO_CLIENT_ID"),
            "clientSecret" => env("AWS_COGNITO_CLIENT_SECRET"),
            "redirectUri" => env("BASE_URL", "http://localhost:8008") . "/auth/aws/callback",
            "urlAuthorize" => "$domain/oauth2/authorize",
            "urlAccessToken" => "$domain/oauth2/token",
            "urlResourceOwnerDetails" => "$domain/oauth2/userInfo",
        ]);
    }
}
