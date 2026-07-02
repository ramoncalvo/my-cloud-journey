<?php

// Version PHP vanilla del mismo lab (python/, csharp/, springboot/,
// nestjs/, express/, go/): 3 vistas publicas y 3 privadas, SSO real por
// OIDC contra cada nube. Sin framework ni dependencias de Composer: solo
// el servidor embebido de PHP + curl para las llamadas HTTP al proveedor.
// Al no haber un proceso que "arranca y construye clientes" (cada request
// ejecuta este script de cero), no hay riesgo de validaciones eager al
// boot como si tuvimos que resolver en Spring Boot, ASP.NET Core, Express
// y NestJS.

session_start();

function env(string $key, string $fallback = ""): string
{
    $value = getenv($key);
    return $value !== false && $value !== "" ? $value : $fallback;
}

const CLOUDS = ["aws", "azure", "gcp"];
const CLOUD_LABEL = ["aws" => "AWS", "azure" => "Azure", "gcp" => "Google Cloud"];

function cloudConfig(string $cloud): array
{
    $baseUrl = env("BASE_URL", "http://localhost:8006");
    $redirectUri = "$baseUrl/auth/$cloud/callback";

    if ($cloud === "aws") {
        $domain = env("AWS_COGNITO_DOMAIN");
        return [
            "client_id" => env("AWS_COGNITO_CLIENT_ID"),
            "client_secret" => env("AWS_COGNITO_CLIENT_SECRET"),
            "authorize_endpoint" => "$domain/oauth2/authorize",
            "token_endpoint" => "$domain/oauth2/token",
            "userinfo_endpoint" => "$domain/oauth2/userInfo",
            "redirect_uri" => $redirectUri,
        ];
    }

    if ($cloud === "azure") {
        $tenant = env("AZURE_TENANT_ID", "common");
        return [
            "client_id" => env("AZURE_CLIENT_ID"),
            "client_secret" => env("AZURE_CLIENT_SECRET"),
            "authorize_endpoint" => "https://login.microsoftonline.com/$tenant/oauth2/v2.0/authorize",
            "token_endpoint" => "https://login.microsoftonline.com/$tenant/oauth2/v2.0/token",
            "userinfo_endpoint" => "https://graph.microsoft.com/oidc/userinfo",
            "redirect_uri" => $redirectUri,
        ];
    }

    // gcp
    return [
        "client_id" => env("GOOGLE_CLIENT_ID"),
        "client_secret" => env("GOOGLE_CLIENT_SECRET"),
        "authorize_endpoint" => "https://accounts.google.com/o/oauth2/v2/auth",
        "token_endpoint" => "https://oauth2.googleapis.com/token",
        "userinfo_endpoint" => "https://openidconnect.googleapis.com/v1/userinfo",
        "redirect_uri" => $redirectUri,
    ];
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

function layout(string $body): string
{
    return <<<HTML
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Multi-cloud SSO Lab (PHP)</title>
  <style>
    body { font-family: system-ui, sans-serif; max-width: 720px; margin: 3rem auto; padding: 0 1rem; }
    nav a { margin-right: 1rem; text-decoration: none; color: #0b5fff; }
    .card { border: 1px solid #ddd; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; }
    .btn { display: inline-block; padding: 0.5rem 1rem; border-radius: 6px; background: #111; color: #fff; text-decoration: none; margin-right: 0.5rem; }
    pre { background: #f6f6f6; padding: 1rem; border-radius: 6px; overflow-x: auto; }
  </style>
</head>
<body>
  <header>
    <a href="/"><strong>Multi-cloud SSO Lab (PHP)</strong></a>
    <nav><a href="/aws">AWS</a><a href="/azure">Azure</a><a href="/gcp">GCP</a></nav>
  </header>
  $body
</body>
</html>
HTML;
}

function isCloud(string $cloud): bool
{
    return in_array($cloud, CLOUDS, true);
}

function redirect(string $location): void
{
    header("Location: $location");
    exit;
}

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$segments = array_values(array_filter(explode("/", $path)));

// GET /
if (count($segments) === 0) {
    echo layout('
        <p>Version PHP vanilla del lab. Cada nube tiene vista publica y privada por SSO (OIDC).</p>
        <div class="card"><h2>AWS</h2><a href="/aws">Vista publica</a> &middot; <a href="/aws/private">Vista privada</a></div>
        <div class="card"><h2>Azure</h2><a href="/azure">Vista publica</a> &middot; <a href="/azure/private">Vista privada</a></div>
        <div class="card"><h2>Google Cloud</h2><a href="/gcp">Vista publica</a> &middot; <a href="/gcp/private">Vista privada</a></div>
    ');
    exit;
}

// GET /auth/{cloud}/login | callback | logout
if ($segments[0] === "auth" && count($segments) === 3) {
    [, $cloud, $action] = $segments;
    if (!isCloud($cloud)) redirect("/");
    $config = cloudConfig($cloud);

    if ($action === "login") {
        $state = bin2hex(random_bytes(16));
        $_SESSION["state_$cloud"] = $state;
        $query = http_build_query([
            "client_id" => $config["client_id"],
            "redirect_uri" => $config["redirect_uri"],
            "response_type" => "code",
            "scope" => "openid email profile",
            "state" => $state,
        ]);
        redirect($config["authorize_endpoint"] . "?" . $query);
    }

    if ($action === "callback") {
        if (($_GET["state"] ?? null) !== ($_SESSION["state_$cloud"] ?? null)) {
            http_response_code(400);
            echo layout("<pre>state invalido</pre>");
            exit;
        }
        $token = httpPostForm($config["token_endpoint"], [
            "grant_type" => "authorization_code",
            "code" => $_GET["code"] ?? "",
            "redirect_uri" => $config["redirect_uri"],
            "client_id" => $config["client_id"],
            "client_secret" => $config["client_secret"],
        ]);
        if (empty($token["access_token"])) {
            http_response_code(500);
            echo layout("<pre>" . htmlspecialchars(json_encode($token)) . "</pre>");
            exit;
        }
        // Igual que en las otras implementaciones: se pide el userinfo
        // endpoint con el access_token en vez de decodificar el id_token.
        $userinfo = httpGetBearer($config["userinfo_endpoint"], $token["access_token"]);
        $_SESSION["user_$cloud"] = $userinfo;
        redirect("/$cloud/private");
    }

    if ($action === "logout") {
        unset($_SESSION["user_$cloud"]);
        redirect("/$cloud");
    }

    redirect("/");
}

// GET /{cloud} | /{cloud}/private
$cloud = $segments[0];
if (!isCloud($cloud)) redirect("/");
$label = CLOUD_LABEL[$cloud];

if (count($segments) === 1) {
    $user = $_SESSION["user_$cloud"] ?? null;
    if ($user) {
        $email = $user["email"] ?? $user["username"] ?? "usuario";
        $body = "<p>Sesion iniciada en $label como <strong>" . htmlspecialchars($email) . "</strong>.</p>
            <a class=\"btn\" href=\"/$cloud/private\">Vista privada</a>
            <a class=\"btn\" href=\"/auth/$cloud/logout\">Cerrar sesion</a>";
    } else {
        $body = "<p>Esta pagina es publica, no requiere autenticacion.</p>
            <a class=\"btn\" href=\"/auth/$cloud/login\">Iniciar sesion con $label</a>";
    }
    echo layout("<h1>$label</h1>$body");
    exit;
}

if (count($segments) === 2 && $segments[1] === "private") {
    $user = $_SESSION["user_$cloud"] ?? null;
    if (!$user) redirect("/auth/$cloud/login");
    $body = "<pre>" . htmlspecialchars(json_encode($user, JSON_PRETTY_PRINT)) . "</pre>
        <a class=\"btn\" href=\"/auth/$cloud/logout\">Cerrar sesion</a>";
    echo layout("<h1>$label</h1>$body");
    exit;
}

redirect("/");
