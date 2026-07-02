const express = require("express");
const session = require("express-session");

const { env } = require("./shared/env");
const { layout } = require("./shared/layout");
const awsRoutes = require("./aws/routes");
const azureRoutes = require("./azure/routes");
const gcpRoutes = require("./gcp/routes");

// Screaming architecture: cada nube es su propia carpeta con client.js
// (wiring OIDC) y routes.js (las 5 rutas de esa nube). server.js no
// conoce ningun detalle de negocio, solo monta cada router.

const app = express();
app.use(
  session({
    secret: env("SESSION_SECRET_KEY", "change-me"),
    resave: false,
    saveUninitialized: false,
  })
);

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

app.use(awsRoutes);
app.use(azureRoutes);
app.use(gcpRoutes);

const port = 8004;
app.listen(port, () => console.log(`Express SSO lab listening on ${port}`));
