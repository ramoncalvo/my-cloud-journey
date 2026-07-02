const express = require("express");
const { client } = require("./client");
const { baseUrl } = require("../shared/env");
const { layout } = require("../shared/layout");

const LABEL = "AWS";
const router = express.Router();

router.get("/aws", (req, res) => {
  const user = req.session.user_aws;
  const body = user
    ? `<p>Sesion iniciada en ${LABEL} como <strong>${user.email || user.username || "usuario"}</strong>.</p>
       <a class="btn" href="/aws/private">Vista privada</a>
       <a class="btn" href="/auth/aws/logout">Cerrar sesion</a>`
    : `<p>Esta pagina es publica, no requiere autenticacion.</p>
       <a class="btn" href="/auth/aws/login">Iniciar sesion con ${LABEL}</a>`;
  res.send(layout(`<h1>${LABEL}</h1>${body}`));
});

router.get("/aws/private", (req, res) => {
  const user = req.session.user_aws;
  if (!user) return res.redirect("/auth/aws/login");
  res.send(
    layout(`<h1>${LABEL}</h1><pre>${JSON.stringify(user, null, 2)}</pre>
    <a class="btn" href="/auth/aws/logout">Cerrar sesion</a>`)
  );
});

router.get("/auth/aws/login", (req, res) => {
  const state = Math.random().toString(36).slice(2);
  req.session.state_aws = state;
  res.redirect(client.authorizationUrl({ scope: "openid email profile", state }));
});

router.get("/auth/aws/callback", async (req, res) => {
  try {
    const params = client.callbackParams(req);
    const expectedState = req.session.state_aws;
    const tokenSet = await client.callback(`${baseUrl()}/auth/aws/callback`, params, {
      state: expectedState,
    });
    // Igual que en las otras implementaciones: userinfo endpoint en vez de
    // confiar solo en las claims del id_token.
    req.session.user_aws = await client.userinfo(tokenSet.access_token);
    res.redirect("/aws/private");
  } catch (err) {
    res.status(500).send(layout(`<pre>${String(err)}</pre>`));
  }
});

router.get("/auth/aws/logout", (req, res) => {
  delete req.session.user_aws;
  res.redirect("/aws");
});

module.exports = router;
