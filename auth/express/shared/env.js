const env = (key, fallback = "") => process.env[key] || fallback;

// openid-client valida client_id no vacio al CONSTRUIR el Client (no solo
// al hacer login), y cada modulo de nube construye su cliente una vez al
// arrancar el proceso. Sin esto, una nube sin configurar tumbaria la app.
const envRequired = (key) => env(key, "unset");

const baseUrl = () => env("BASE_URL", "http://localhost:8004");

module.exports = { env, envRequired, baseUrl };
