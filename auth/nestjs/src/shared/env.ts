export const env = (key: string, fallback = "") => process.env[key] || fallback;

// openid-client valida client_id no vacio al CONSTRUIR el Client (no solo
// al hacer login), y cada *ClientService se instancia una vez al arrancar
// Nest. Sin esto, una nube sin configurar tumbaria toda la app al boot.
export const envRequired = (key: string) => env(key, "unset");

export const baseUrl = () => env("BASE_URL", "http://localhost:8003");
