import { Controller, Get, Req, Res } from "@nestjs/common";
import type { Request, Response } from "express";

import { baseUrl } from "../shared/env";
import { layout } from "../shared/layout";
import { AwsClientService } from "./aws-client.service";

const LABEL = "AWS";

@Controller()
export class AwsController {
  constructor(private readonly aws: AwsClientService) {}

  @Get("aws")
  publicView(@Req() req: Request, @Res() res: Response) {
    const user = (req.session as any).user_aws;
    const body = user
      ? `<p>Sesion iniciada en ${LABEL} como <strong>${user.email || user.username || "usuario"}</strong>.</p>
         <a class="btn" href="/aws/private">Vista privada</a>
         <a class="btn" href="/auth/aws/logout">Cerrar sesion</a>`
      : `<p>Esta pagina es publica, no requiere autenticacion.</p>
         <a class="btn" href="/auth/aws/login">Iniciar sesion con ${LABEL}</a>`;
    res.send(layout(`<h1>${LABEL}</h1>${body}`));
  }

  @Get("aws/private")
  privateView(@Req() req: Request, @Res() res: Response) {
    const user = (req.session as any).user_aws;
    if (!user) return res.redirect("/auth/aws/login");
    res.send(
      layout(`<h1>${LABEL}</h1><pre>${JSON.stringify(user, null, 2)}</pre>
      <a class="btn" href="/auth/aws/logout">Cerrar sesion</a>`)
    );
  }

  @Get("auth/aws/login")
  login(@Req() req: Request, @Res() res: Response) {
    const state = Math.random().toString(36).slice(2);
    (req.session as any).state_aws = state;
    res.redirect(this.aws.client.authorizationUrl({ scope: "openid email profile", state }));
  }

  @Get("auth/aws/callback")
  async callback(@Req() req: Request, @Res() res: Response) {
    try {
      const expectedState = (req.session as any).state_aws;
      const params = this.aws.client.callbackParams(req);
      const tokenSet = await this.aws.client.callback(`${baseUrl()}/auth/aws/callback`, params, {
        state: expectedState,
      });
      // Igual que en las otras implementaciones: userinfo endpoint en vez
      // de confiar solo en las claims del id_token.
      const user = await this.aws.client.userinfo(tokenSet.access_token!);
      (req.session as any).user_aws = user;
      res.redirect("/aws/private");
    } catch (err) {
      res.status(500).send(layout(`<pre>${String(err)}</pre>`));
    }
  }

  @Get("auth/aws/logout")
  logout(@Req() req: Request, @Res() res: Response) {
    delete (req.session as any).user_aws;
    res.redirect("/aws");
  }
}
