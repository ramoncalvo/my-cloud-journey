import { Injectable } from "@nestjs/common";
import { Issuer, Client } from "openid-client";

export const CLOUDS = ["aws", "azure", "gcp"] as const;
export type Cloud = (typeof CLOUDS)[number];
export const CLOUD_LABEL: Record<Cloud, string> = {
  aws: "AWS",
  azure: "Azure",
  gcp: "Google Cloud",
};

const env = (key: string, fallback = "") => process.env[key] || fallback;
// openid-client valida client_id no vacio al CONSTRUIR el Client (no solo
// al hacer login), y AuthService se instancia una vez al arrancar Nest.
// Sin esto, una nube sin configurar tumbaria toda la app al boot.
const envRequired = (key: string) => env(key, "unset");

/**
 * Igual que en la version Express: los 3 Issuer se construyen a mano (sin
 * .well-known/openid-configuration en el arranque), asi la app sirve las
 * rutas publicas igual aunque una nube no este configurada todavia.
 */
@Injectable()
export class AuthService {
  private readonly baseUrl = env("BASE_URL", "http://localhost:8003");
  private readonly clients: Record<Cloud, Client>;

  constructor() {
    this.clients = {
      aws: this.buildClient("aws"),
      azure: this.buildClient("azure"),
      gcp: this.buildClient("gcp"),
    };
  }

  private buildClient(cloud: Cloud): Client {
    let issuerMetadata: ConstructorParameters<typeof Issuer>[0];
    let clientId: string;
    let clientSecret: string;

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
      redirect_uris: [`${this.baseUrl}/auth/${cloud}/callback`],
      response_types: ["code"],
    });
  }

  authorizationUrl(cloud: Cloud, state: string): string {
    return this.clients[cloud].authorizationUrl({
      scope: "openid email profile",
      state,
    });
  }

  async handleCallback(cloud: Cloud, params: Record<string, unknown>, expectedState: string) {
    const client = this.clients[cloud];
    const tokenSet = await client.callback(`${this.baseUrl}/auth/${cloud}/callback`, params, {
      state: expectedState,
    });
    // Igual que en las otras implementaciones: userinfo endpoint en vez de
    // confiar solo en las claims del id_token.
    return client.userinfo(tokenSet.access_token!);
  }
}
