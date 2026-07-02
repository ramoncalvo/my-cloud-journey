import { Controller, Get, Res } from "@nestjs/common";
import type { Response } from "express";

import { layout } from "./shared/layout";

@Controller()
export class AppController {
  @Get("/")
  index(@Res() res: Response) {
    res.send(
      layout(`
      <p>Version NestJS del lab. Cada nube tiene vista publica y privada por SSO (OIDC).</p>
      <div class="card"><h2>AWS</h2><a href="/aws">Vista publica</a> &middot; <a href="/aws/private">Vista privada</a></div>
      <div class="card"><h2>Azure</h2><a href="/azure">Vista publica</a> &middot; <a href="/azure/private">Vista privada</a></div>
      <div class="card"><h2>Google Cloud</h2><a href="/gcp">Vista publica</a> &middot; <a href="/gcp/private">Vista privada</a></div>
    `)
    );
  }
}
