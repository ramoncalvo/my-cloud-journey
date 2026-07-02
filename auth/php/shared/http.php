<?php

function env(string $key, string $fallback = ""): string
{
    $value = getenv($key);
    return $value !== false && $value !== "" ? $value : $fallback;
}

function httpPostForm(string $url, array $fields): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($fields),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Content-Type: application/x-www-form-urlencoded"],
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    return json_decode($body ?: "{}", true) ?? [];
}

function httpGetBearer(string $url, string $accessToken): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Authorization: Bearer $accessToken"],
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    return json_decode($body ?: "{}", true) ?? [];
}

function redirectTo(string $location): void
{
    header("Location: $location");
    exit;
}
