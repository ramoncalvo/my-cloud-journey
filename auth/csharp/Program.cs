using Microsoft.AspNetCore.Authentication;
using Microsoft.AspNetCore.Authentication.OpenIdConnect;
using Microsoft.IdentityModel.Protocols.OpenIdConnect;

// Version en ASP.NET Core del mismo lab que ya existe en python/ y springboot/:
// 3 vistas publicas (/aws, /azure, /gcp) y 3 privadas (/aws/private, ...),
// cada una protegida con el SSO real de su nube via OIDC (OpenID Connect).
//
// Diferencia clave respecto a Python: aqui cada nube tiene su propio
// "cookie scheme" (cookies-aws, cookies-azure, cookies-gcp), asi que puedes
// estar logueado en unas nubes y no en otras a la vez, igual que las claves
// de sesion user_aws/user_azure/user_gcp en el auth.py de Python.

var builder = WebApplication.CreateBuilder(args);

static string Env(string key, string fallback = "") =>
    Environment.GetEnvironmentVariable(key) ?? fallback;

// OpenIdConnectOptions.Validate() exige ClientId no vacio, y corre en
// CADA request (el middleware de auth inicializa los 3 handlers remotos
// para comprobar su CallbackPath, no solo al hacer login). Si una nube
// aun no esta configurada en .env, ClientId llega como cadena vacia y
// tumbaria hasta las rutas publicas con un 500. Por eso el fallback es
// "unset" y no "": la app arranca y sirve igual, el login de esa nube
// fallara mas tarde, al intentarlo (igual que en Spring Boot).
static string EnvRequired(string key) =>
    string.IsNullOrEmpty(Environment.GetEnvironmentVariable(key)) ? "unset" : Environment.GetEnvironmentVariable(key)!;

var clouds = new[] { "aws", "azure", "gcp" };

// Nombre del esquema OIDC registrado por nube. "gcp" usa el nombre interno
// "oidc-google" solo por claridad, no tiene relacion con el paquete
// Microsoft.AspNetCore.Authentication.Google (que no usamos, seguimos OIDC puro).
var oidcScheme = new Dictionary<string, string>
{
    ["aws"] = "oidc-aws",
    ["azure"] = "oidc-azure",
    ["gcp"] = "oidc-google",
};

static string CookieScheme(string cloud) => $"cookies-{cloud}";

var authBuilder = builder.Services.AddAuthentication();

foreach (var cloud in clouds)
{
    // Un esquema de cookie por nube: guarda la sesion de esa nube en su
    // propia cookie, independiente de las otras dos.
    authBuilder.AddCookie(CookieScheme(cloud));
}

// Azure AD (Microsoft Entra ID): soporta descubrimiento OIDC estandar,
// Authority + el SDK resuelve solos authorize/token/jwks.
authBuilder.AddOpenIdConnect(oidcScheme["azure"], options =>
{
    options.SignInScheme = CookieScheme("azure");
    options.Authority = $"https://login.microsoftonline.com/{Env("AZURE_TENANT_ID")}/v2.0";
    options.ClientId = EnvRequired("AZURE_CLIENT_ID");
    options.ClientSecret = Env("AZURE_CLIENT_SECRET");
    options.ResponseType = OpenIdConnectResponseType.Code;
    options.CallbackPath = "/auth/azure/callback";
    options.SaveTokens = false;
    options.Scope.Clear();
    options.Scope.Add("openid");
    options.Scope.Add("email");
    options.Scope.Add("profile");
});

// Google: tambien soporta descubrimiento OIDC estandar via MetadataAddress.
authBuilder.AddOpenIdConnect(oidcScheme["gcp"], options =>
{
    options.SignInScheme = CookieScheme("gcp");
    options.MetadataAddress = "https://accounts.google.com/.well-known/openid-configuration";
    options.ClientId = EnvRequired("GOOGLE_CLIENT_ID");
    options.ClientSecret = Env("GOOGLE_CLIENT_SECRET");
    options.ResponseType = OpenIdConnectResponseType.Code;
    options.CallbackPath = "/auth/gcp/callback";
    options.SaveTokens = false;
    options.Scope.Clear();
    options.Scope.Add("openid");
    options.Scope.Add("email");
    options.Scope.Add("profile");
});

// AWS Cognito: su documento OIDC no expone authorization_endpoint ni
// token_endpoint (viven bajo el dominio del Hosted UI), asi que en vez de
// MetadataAddress se construye la configuracion a mano, igual que en Python.
authBuilder.AddOpenIdConnect(oidcScheme["aws"], options =>
{
    var domain = Env("AWS_COGNITO_DOMAIN");
    options.SignInScheme = CookieScheme("aws");
    options.ClientId = EnvRequired("AWS_COGNITO_CLIENT_ID");
    options.ClientSecret = Env("AWS_COGNITO_CLIENT_SECRET");
    options.ResponseType = OpenIdConnectResponseType.Code;
    options.CallbackPath = "/auth/aws/callback";
    options.SaveTokens = false;
    options.GetClaimsFromUserInfoEndpoint = true;
    options.Scope.Clear();
    options.Scope.Add("openid");
    options.Scope.Add("email");
    options.Scope.Add("profile");
    options.Configuration = new OpenIdConnectConfiguration
    {
        Issuer = domain,
        AuthorizationEndpoint = $"{domain}/oauth2/authorize",
        TokenEndpoint = $"{domain}/oauth2/token",
        UserInfoEndpoint = $"{domain}/oauth2/userInfo",
    };
});

var app = builder.Build();
app.UseAuthentication();

static string CloudLabel(string cloud) => cloud switch
{
    "aws" => "AWS",
    "azure" => "Azure",
    "gcp" => "Google Cloud",
    _ => cloud,
};

// $$""" (doble $) hace falta porque el CSS de abajo usa llaves simples
// { } como texto literal; con un solo $ cualquier "{" se interpretaria
// como inicio de interpolacion. Con $$, la interpolacion pasa a requerir
// llaves dobles {{ }} (ver {{body}} al final), y las llaves simples del
// CSS quedan como texto normal.
static string Layout(string body) => $$"""
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Multi-cloud SSO Lab (C#)</title>
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
    <a href="/"><strong>Multi-cloud SSO Lab (C#)</strong></a>
    <nav><a href="/aws">AWS</a><a href="/azure">Azure</a><a href="/gcp">GCP</a></nav>
  </header>
  {{body}}
</body>
</html>
""";

app.MapGet("/", () => Results.Content(Layout("""
<p>Version ASP.NET Core del lab. Cada nube tiene vista publica y privada por SSO (OIDC).</p>
<div class="card"><h2>AWS</h2><a href="/aws">Vista publica</a> &middot; <a href="/aws/private">Vista privada</a></div>
<div class="card"><h2>Azure</h2><a href="/azure">Vista publica</a> &middot; <a href="/azure/private">Vista privada</a></div>
<div class="card"><h2>Google Cloud</h2><a href="/gcp">Vista publica</a> &middot; <a href="/gcp/private">Vista privada</a></div>
"""), "text/html"));

app.MapGet("/{cloud}", async (HttpContext ctx, string cloud) =>
{
    if (!clouds.Contains(cloud))
    {
        return Results.Redirect("/");
    }

    var result = await ctx.AuthenticateAsync(CookieScheme(cloud));
    var label = CloudLabel(cloud);

    string body;
    if (result.Succeeded)
    {
        var email = result.Principal?.FindFirst("email")?.Value
            ?? result.Principal?.Identity?.Name
            ?? "usuario";
        body = $"""
        <p>Sesion iniciada en {label} como <strong>{email}</strong>.</p>
        <a class="btn" href="/{cloud}/private">Vista privada</a>
        <a class="btn" href="/auth/{cloud}/logout">Cerrar sesion</a>
        """;
    }
    else
    {
        body = $"""
        <p>Esta pagina es publica, no requiere autenticacion.</p>
        <a class="btn" href="/auth/{cloud}/login">Iniciar sesion con {label}</a>
        """;
    }

    return Results.Content(Layout($"<h1>{label}</h1>{body}"), "text/html");
});

app.MapGet("/{cloud}/private", async (HttpContext ctx, string cloud) =>
{
    if (!clouds.Contains(cloud))
    {
        return Results.Redirect("/");
    }

    var result = await ctx.AuthenticateAsync(CookieScheme(cloud));
    if (!result.Succeeded || result.Principal is null)
    {
        // Sin sesion para esta nube: al login SSO de esa nube, igual que
        // el equivalente en Python (RedirectResponse a /auth/{cloud}/login).
        return Results.Redirect($"/auth/{cloud}/login");
    }

    var claims = string.Join("\n", result.Principal.Claims.Select(c => $"{c.Type}: {c.Value}"));
    var body = $"""
    <pre>{claims}</pre>
    <a class="btn" href="/auth/{cloud}/logout">Cerrar sesion</a>
    """;
    return Results.Content(Layout($"<h1>{CloudLabel(cloud)}</h1>{body}"), "text/html");
});

app.MapGet("/auth/{cloud}/login", (string cloud) =>
{
    if (!oidcScheme.TryGetValue(cloud, out var scheme))
    {
        return Results.Redirect("/");
    }

    // Results.Challenge dispara el handshake OIDC: redirige al proveedor
    // con client_id, redirect_uri, scope y state (para prevenir CSRF).
    var props = new AuthenticationProperties { RedirectUri = $"/{cloud}/private" };
    return Results.Challenge(props, new[] { scheme });
});

app.MapGet("/auth/{cloud}/logout", async (HttpContext ctx, string cloud) =>
{
    // Logout local: borra solo la cookie de esta app para esta nube, no
    // cierra sesion en Cognito/Azure/Google (logout federado no incluido).
    if (clouds.Contains(cloud))
    {
        await ctx.SignOutAsync(CookieScheme(cloud));
    }
    return Results.Redirect($"/{cloud}");
});

app.Run();
