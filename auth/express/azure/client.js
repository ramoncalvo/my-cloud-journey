const { Issuer } = require("openid-client");
const { env, envRequired, baseUrl } = require("../shared/env");

const tenant = env("AZURE_TENANT_ID", "common");
const issuer = new Issuer({
  issuer: `https://login.microsoftonline.com/${tenant}/v2.0`,
  authorization_endpoint: `https://login.microsoftonline.com/${tenant}/oauth2/v2.0/authorize`,
  token_endpoint: `https://login.microsoftonline.com/${tenant}/oauth2/v2.0/token`,
  userinfo_endpoint: "https://graph.microsoft.com/oidc/userinfo",
});

const client = new issuer.Client({
  client_id: envRequired("AZURE_CLIENT_ID"),
  client_secret: env("AZURE_CLIENT_SECRET"),
  redirect_uris: [`${baseUrl()}/auth/azure/callback`],
  response_types: ["code"],
});

module.exports = { client };
