const { Issuer } = require("openid-client");
const { env, envRequired, baseUrl } = require("../shared/env");

const issuer = new Issuer({
  issuer: "https://accounts.google.com",
  authorization_endpoint: "https://accounts.google.com/o/oauth2/v2/auth",
  token_endpoint: "https://oauth2.googleapis.com/token",
  userinfo_endpoint: "https://openidconnect.googleapis.com/v1/userinfo",
});

const client = new issuer.Client({
  client_id: envRequired("GOOGLE_CLIENT_ID"),
  client_secret: env("GOOGLE_CLIENT_SECRET"),
  redirect_uris: [`${baseUrl()}/auth/gcp/callback`],
  response_types: ["code"],
});

module.exports = { client };
