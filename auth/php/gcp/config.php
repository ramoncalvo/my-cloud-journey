<?php

function gcp_config(): array
{
    return [
        "client_id" => env("GOOGLE_CLIENT_ID"),
        "client_secret" => env("GOOGLE_CLIENT_SECRET"),
        "authorize_endpoint" => "https://accounts.google.com/o/oauth2/v2/auth",
        "token_endpoint" => "https://oauth2.googleapis.com/token",
        "userinfo_endpoint" => "https://openidconnect.googleapis.com/v1/userinfo",
        "redirect_uri" => env("BASE_URL", "http://localhost:8006") . "/auth/gcp/callback",
    ];
}
