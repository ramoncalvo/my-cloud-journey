const express = require("express");
const { client } = require("./client");
const { baseUrl } = require("../shared/env");
const { layout } = require("../shared/layout");

const LABEL = "Azure";
const router = express.Router();

router.get("/azure", (req, res) => {
  const user = req.session.user_azure;
  const body = user
    ? `<p>Sesion iniciada en ${LABEL} como <strong>${user.email || user.username || "usuario"}</strong>.</p>
       <a class="btn" href="/azure/private">Vista privada</a>
       <a class="btn" href="/auth/azure/logout">Cerrar sesion</a>`
    : `<p>Esta pagina es publica, no requiere autenticacion.</p>
       <a class="btn" href="/auth/azure/login">Iniciar sesion con ${LABEL}</a>`;
  res.send(layout(`<h1>${LABEL}</h1>${body}`));
});

router.get("/azure/private", (req, res) => {
  const user = req.session.user_azure;
  if (!user) return res.redirect("/auth/azure/login");
  res.send(
    layout(`<h1>${LABEL}</h1><pre>${JSON.stringify(user, null, 2)}</pre>
    <a class="btn" href="/auth/azure/logout">Cerrar sesion</a>`)
  );
});

router.get("/auth/azure/login", (req, res) => {
  const state = Math.random().toString(36).slice(2);
  req.session.state_azure = state;
  res.redirect(client.authorizationUrl({ scope: "openid email profile", state }));
});

router.get("/auth/azure/callback", async (req, res) => {
  try {
    const params = client.callbackParams(req);
    const expectedState = req.session.state_azure;
    const tokenSet = await client.callback(`${baseUrl()}/auth/azure/callback`, params, {
      state: expectedState,
    });
    req.session.user_azure = await client.userinfo(tokenSet.access_token);
    res.redirect("/azure/private");
  } catch (err) {
    res.status(500).send(layout(`<pre>${String(err)}</pre>`));
  }
});

router.get("/auth/azure/logout", (req, res) => {
  delete req.session.user_azure;
  res.redirect("/azure");
});

module.exports = router;
