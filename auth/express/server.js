const express = require("express");
const session = require("express-session");
const { Issuer } = require("openid-client");

// Version Node/Express del mismo lab que python/, csharp/ y springboot/:
// 3 vistas publicas y 3 privadas, SSO real por OIDC contra cada nube.
//
// A diferencia de Authlib (Python) o el handler de ASP.NET Core, aqui los
// 3 "Issuer" se construyen a mano (sin llamar a .well-known/openid-configuration
// al arrancar): asi la app sirve las rutas publicas igual aunque una nube
// no este configurada todavia, sin el riesgo de validaciones "eager" al
// boot que si tuvimos que resolver en Spring Boot y ASP.NET Core.

const env = (key, fallback = "") => process.env[key] || fallback;
// openid-client valida que client_id no este vacio al CONSTRUIR el
// Client (no solo al hacer login), y esto pasa una vez al arrancar el
// proceso. Si una nube no esta configurada en .env, client_id llegaria
// vacio y tumbaria toda la app. Por eso el fallback es "unset": la app
// arranca y sirve las rutas publicas igual, el login de esa nube
// fallara mas tarde, al intentarlo (mismo patron que en Spring Boot y C#).
const envRequired = (key) => env(key, "unset");

const BASE_URL = env("BASE_URL", "http://localhost:8004");
const CLOUDS = ["aws", "azure", "gcp"];
const CLOUD_LABEL = { aws: "AWS", azure: "Azure", gcp: "Google Cloud" };

function buildClient(cloud) {
  let issuerMetadata;
  let clientId;
  let clientSecret;

  if (cloud === "aws") {
    const domain = env("AWS_COGNITO_DOMAIN");
    issuerMetadata = {
      issuer: domain,
      authorization_endpoint: `${domain}/oauth2/authorize`,
      token_endpoint: `${domain}/oauth2/token`,
      userinfo_endpoint: `${domain}/oauth2/userInfo`,
    };
    clientId = envRequired("AWS_COGNITO_CLIENT_ID");
    clientSecret = env("AWS_COGNITO_CLIENT_SECRET");
  } else if (cloud === "azure") {
    const tenant = env("AZURE_TENANT_ID", "common");
    issuerMetadata = {
      issuer: `https://login.microsoftonline.com/${tenant}/v2.0`,
      authorization_endpoint: `https://login.microsoftonline.com/${tenant}/oauth2/v2.0/authorize`,
      token_endpoint: `https://login.microsoftonline.com/${tenant}/oauth2/v2.0/token`,
      userinfo_endpoint: "https://graph.microsoft.com/oidc/userinfo",
    };
    clientId = envRequired("AZURE_CLIENT_ID");
    clientSecret = env("AZURE_CLIENT_SECRET");
  } else {
    issuerMetadata = {
      issuer: "https://accounts.google.com",
      authorization_endpoint: "https://accounts.google.com/o/oauth2/v2/auth",
      token_endpoint: "https://oauth2.googleapis.com/token",
      userinfo_endpoint: "https://openidconnect.googleapis.com/v1/userinfo",
    };
    clientId = envRequired("GOOGLE_CLIENT_ID");
    clientSecret = env("GOOGLE_CLIENT_SECRET");
  }

  const issuer = new Issuer(issuerMetadata);
  return new issuer.Client({
    client_id: clientId,
    client_secret: clientSecret,
    redirect_uris: [`${BASE_URL}/auth/${cloud}/callback`],
    response_types: ["code"],
  });
}

const clients = Object.fromEntries(CLOUDS.map((cloud) => [cloud, buildClient(cloud)]));

const app = express();
app.use(
  session({
    secret: env("SESSION_SECRET_KEY", "change-me"),
    resave: false,
    saveUninitialized: false,
  })
);

function layout(body) {
  return `<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Multi-cloud SSO Lab (Express)</title>
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
    <a href="/"><strong>Multi-cloud SSO Lab (Express)</strong></a>
    <nav><a href="/aws">AWS</a><a href="/azure">Azure</a><a href="/gcp">GCP</a></nav>
  </header>
  ${body}
</body>
</html>`;
}

app.get("/", (req, res) => {
  res.send(
    layout(`
    <p>Version Node/Express del lab. Cada nube tiene vista publica y privada por SSO (OIDC).</p>
    <div class="card"><h2>AWS</h2><a href="/aws">Vista publica</a> &middot; <a href="/aws/private">Vista privada</a></div>
    <div class="card"><h2>Azure</h2><a href="/azure">Vista publica</a> &middot; <a href="/azure/private">Vista privada</a></div>
    <div class="card"><h2>Google Cloud</h2><a href="/gcp">Vista publica</a> &middot; <a href="/gcp/private">Vista privada</a></div>
  `)
  );
});

app.get("/:cloud", (req, res) => {
  const { cloud } = req.params;
  if (!CLOUDS.includes(cloud)) return res.redirect("/");

  const label = CLOUD_LABEL[cloud];
  const user = req.session[`user_${cloud}`];
  const body = user
    ? `<p>Sesion iniciada en ${label} como <strong>${user.email || user.username || "usuario"}</strong>.</p>
       <a class="btn" href="/${cloud}/private">Vista privada</a>
       <a class="btn" href="/auth/${cloud}/logout">Cerrar sesion</a>`
    : `<p>Esta pagina es publica, no requiere autenticacion.</p>
       <a class="btn" href="/auth/${cloud}/login">Iniciar sesion con ${label}</a>`;

  res.send(layout(`<h1>${label}</h1>${body}`));
});

app.get("/:cloud/private", (req, res) => {
  const { cloud } = req.params;
  if (!CLOUDS.includes(cloud)) return res.redirect("/");

  const user = req.session[`user_${cloud}`];
  if (!user) return res.redirect(`/auth/${cloud}/login`);

  res.send(
    layout(`<h1>${CLOUD_LABEL[cloud]}</h1><pre>${JSON.stringify(user, null, 2)}</pre>
    <a class="btn" href="/auth/${cloud}/logout">Cerrar sesion</a>`)
  );
});

app.get("/auth/:cloud/login", (req, res) => {
  const { cloud } = req.params;
  if (!CLOUDS.includes(cloud)) return res.redirect("/");

  const state = Math.random().toString(36).slice(2);
  req.session[`state_${cloud}`] = state;
  const url = clients[cloud].authorizationUrl({
    scope: "openid email profile",
    state,
  });
  res.redirect(url);
});

app.get("/auth/:cloud/callback", async (req, res) => {
  const { cloud } = req.params;
  if (!CLOUDS.includes(cloud)) return res.redirect("/");

  try {
    const client = clients[cloud];
    const params = client.callbackParams(req);
    const expectedState = req.session[`state_${cloud}`];
    const tokenSet = await client.callback(`${BASE_URL}/auth/${cloud}/callback`, params, {
      state: expectedState,
    });

    // Igual que en las otras implementaciones: se pide el userinfo
    // endpoint en vez de confiar solo en las claims del id_token.
    const userinfo = await client.userinfo(tokenSet.access_token);
    req.session[`user_${cloud}`] = userinfo;
    res.redirect(`/${cloud}/private`);
  } catch (err) {
    res.status(500).send(layout(`<pre>${String(err)}</pre>`));
  }
});

app.get("/auth/:cloud/logout", (req, res) => {
  const { cloud } = req.params;
  if (CLOUDS.includes(cloud)) delete req.session[`user_${cloud}`];
  res.redirect(`/${cloud}`);
});

const port = 8004;
app.listen(port, () => console.log(`Express SSO lab listening on ${port}`));
