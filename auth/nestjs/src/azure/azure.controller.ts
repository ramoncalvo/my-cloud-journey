import { Controller, Get, Req, Res } from "@nestjs/common";
import type { Request, Response } from "express";

import { baseUrl } from "../shared/env";
import { layout } from "../shared/layout";
import { AzureClientService } from "./azure-client.service";

const LABEL = "Azure";

@Controller()
export class AzureController {
  constructor(private readonly azure: AzureClientService) {}

  @Get("azure")
  publicView(@Req() req: Request, @Res() res: Response) {
    const user = (req.session as any).user_azure;
    const body = user
      ? `<p>Sesion iniciada en ${LABEL} como <strong>${user.email || user.username || "usuario"}</strong>.</p>
         <a class="btn" href="/azure/private">Vista privada</a>
         <a class="btn" href="/auth/azure/logout">Cerrar sesion</a>`
      : `<p>Esta pagina es publica, no requiere autenticacion.</p>
         <a class="btn" href="/auth/azure/login">Iniciar sesion con ${LABEL}</a>`;
    res.send(layout(`<h1>${LABEL}</h1>${body}`));
  }

  @Get("azure/private")
  privateView(@Req() req: Request, @Res() res: Response) {
    const user = (req.session as any).user_azure;
    if (!user) return res.redirect("/auth/azure/login");
    res.send(
      layout(`<h1>${LABEL}</h1><pre>${JSON.stringify(user, null, 2)}</pre>
      <a class="btn" href="/auth/azure/logout">Cerrar sesion</a>`)
    );
  }

  @Get("auth/azure/login")
  login(@Req() req: Request, @Res() res: Response) {
    const state = Math.random().toString(36).slice(2);
    (req.session as any).state_azure = state;
    res.redirect(this.azure.client.authorizationUrl({ scope: "openid email profile", state }));
  }

  @Get("auth/azure/callback")
  async callback(@Req() req: Request, @Res() res: Response) {
    try {
      const expectedState = (req.session as any).state_azure;
      const params = this.azure.client.callbackParams(req);
      const tokenSet = await this.azure.client.callback(`${baseUrl()}/auth/azure/callback`, params, {
        state: expectedState,
      });
      const user = await this.azure.client.userinfo(tokenSet.access_token!);
      (req.session as any).user_azure = user;
      res.redirect("/azure/private");
    } catch (err) {
      res.status(500).send(layout(`<pre>${String(err)}</pre>`));
    }
  }

  @Get("auth/azure/logout")
  logout(@Req() req: Request, @Res() res: Response) {
    delete (req.session as any).user_azure;
    res.redirect("/azure");
  }
}
