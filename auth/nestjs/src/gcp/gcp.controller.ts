import { Controller, Get, Req, Res } from "@nestjs/common";
import type { Request, Response } from "express";

import { baseUrl } from "../shared/env";
import { layout } from "../shared/layout";
import { GcpClientService } from "./gcp-client.service";

const LABEL = "Google Cloud";

@Controller()
export class GcpController {
  constructor(private readonly gcp: GcpClientService) {}

  @Get("gcp")
  publicView(@Req() req: Request, @Res() res: Response) {
    const user = (req.session as any).user_gcp;
    const body = user
      ? `<p>Sesion iniciada en ${LABEL} como <strong>${user.email || user.username || "usuario"}</strong>.</p>
         <a class="btn" href="/gcp/private">Vista privada</a>
         <a class="btn" href="/auth/gcp/logout">Cerrar sesion</a>`
      : `<p>Esta pagina es publica, no requiere autenticacion.</p>
         <a class="btn" href="/auth/gcp/login">Iniciar sesion con ${LABEL}</a>`;
    res.send(layout(`<h1>${LABEL}</h1>${body}`));
  }

  @Get("gcp/private")
  privateView(@Req() req: Request, @Res() res: Response) {
    const user = (req.session as any).user_gcp;
    if (!user) return res.redirect("/auth/gcp/login");
    res.send(
      layout(`<h1>${LABEL}</h1><pre>${JSON.stringify(user, null, 2)}</pre>
      <a class="btn" href="/auth/gcp/logout">Cerrar sesion</a>`)
    );
  }

  @Get("auth/gcp/login")
  login(@Req() req: Request, @Res() res: Response) {
    const state = Math.random().toString(36).slice(2);
    (req.session as any).state_gcp = state;
    res.redirect(this.gcp.client.authorizationUrl({ scope: "openid email profile", state }));
  }

  @Get("auth/gcp/callback")
  async callback(@Req() req: Request, @Res() res: Response) {
    try {
      const expectedState = (req.session as any).state_gcp;
      const params = this.gcp.client.callbackParams(req);
      const tokenSet = await this.gcp.client.callback(`${baseUrl()}/auth/gcp/callback`, params, {
        state: expectedState,
      });
      const user = await this.gcp.client.userinfo(tokenSet.access_token!);
      (req.session as any).user_gcp = user;
      res.redirect("/gcp/private");
    } catch (err) {
      res.status(500).send(layout(`<pre>${String(err)}</pre>`));
    }
  }

  @Get("auth/gcp/logout")
  logout(@Req() req: Request, @Res() res: Response) {
    delete (req.session as any).user_gcp;
    res.redirect("/gcp");
  }
}
