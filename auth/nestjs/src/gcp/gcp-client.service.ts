import { Injectable } from "@nestjs/common";
import { Issuer, Client } from "openid-client";

import { baseUrl, env, envRequired } from "../shared/env";

@Injectable()
export class GcpClientService {
  readonly client: Client;

  constructor() {
    const issuer = new Issuer({
      issuer: "https://accounts.google.com",
      authorization_endpoint: "https://accounts.google.com/o/oauth2/v2/auth",
      token_endpoint: "https://oauth2.googleapis.com/token",
      userinfo_endpoint: "https://openidconnect.googleapis.com/v1/userinfo",
    });

    this.client = new issuer.Client({
      client_id: envRequired("GOOGLE_CLIENT_ID"),
      client_secret: env("GOOGLE_CLIENT_SECRET"),
      redirect_uris: [`${baseUrl()}/auth/gcp/callback`],
      response_types: ["code"],
    });
  }
}
