const express = require("express");
const { client } = require("./client");
const { baseUrl } = require("../shared/env");
const { layout } = require("../shared/layout");

const LABEL = "Google Cloud";
const router = express.Router();

router.get("/gcp", (req, res) => {
  const user = req.session.user_gcp;
  const body = user
    ? `<p>Sesion iniciada en ${LABEL} como <strong>${user.email || user.username || "usuario"}</strong>.</p>
       <a class="btn" href="/gcp/private">Vista privada</a>
       <a class="btn" href="/auth/gcp/logout">Cerrar sesion</a>`
    : `<p>Esta pagina es publica, no requiere autenticacion.</p>
       <a class="btn" href="/auth/gcp/login">Iniciar sesion con ${LABEL}</a>`;
  res.send(layout(`<h1>${LABEL}</h1>${body}`));
});

router.get("/gcp/private", (req, res) => {
  const user = req.session.user_gcp;
  if (!user) return res.redirect("/auth/gcp/login");
  res.send(
    layout(`<h1>${LABEL}</h1><pre>${JSON.stringify(user, null, 2)}</pre>
    <a class="btn" href="/auth/gcp/logout">Cerrar sesion</a>`)
  );
});

router.get("/auth/gcp/login", (req, res) => {
  const state = Math.random().toString(36).slice(2);
  req.session.state_gcp = state;
  res.redirect(client.authorizationUrl({ scope: "openid email profile", state }));
});

router.get("/auth/gcp/callback", async (req, res) => {
  try {
    const params = client.callbackParams(req);
    const expectedState = req.session.state_gcp;
    const tokenSet = await client.callback(`${baseUrl()}/auth/gcp/callback`, params, {
      state: expectedState,
    });
    req.session.user_gcp = await client.userinfo(tokenSet.access_token);
    res.redirect("/gcp/private");
  } catch (err) {
    res.status(500).send(layout(`<pre>${String(err)}</pre>`));
  }
});

router.get("/auth/gcp/logout", (req, res) => {
  delete req.session.user_gcp;
  res.redirect("/gcp");
});

module.exports = router;
