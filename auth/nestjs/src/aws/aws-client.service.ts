import { Injectable } from "@nestjs/common";
import { Issuer, Client } from "openid-client";

import { baseUrl, env, envRequired } from "../shared/env";

@Injectable()
export class AwsClientService {
  readonly client: Client;

  constructor() {
    // El documento OIDC de Cognito no expone authorization_endpoint ni
    // token_endpoint (viven bajo el dominio del Hosted UI), asi que el
    // Issuer se construye a mano en vez de usar Issuer.discover().
    const domain = env("AWS_COGNITO_DOMAIN");
    const issuer = new Issuer({
      issuer: domain,
      authorization_endpoint: `${domain}/oauth2/authorize`,
      token_endpoint: `${domain}/oauth2/token`,
      userinfo_endpoint: `${domain}/oauth2/userInfo`,
    });

    this.client = new issuer.Client({
      client_id: envRequired("AWS_COGNITO_CLIENT_ID"),
      client_secret: env("AWS_COGNITO_CLIENT_SECRET"),
      redirect_uris: [`${baseUrl()}/auth/aws/callback`],
      response_types: ["code"],
    });
  }
}
