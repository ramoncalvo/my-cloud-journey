<?php

function env(string $key, string $fallback = ""): string
{
    $value = getenv($key);
    return $value !== false && $value !== "" ? $value : $fallback;
}

function baseUrl(): string
{
    return env("BASE_URL", "http://localhost:8007");
}
