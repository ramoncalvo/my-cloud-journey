import { Controller, Get, Param, Req, Res } from "@nestjs/common";
import type { Request, Response } from "express";
import { AuthService, CLOUDS, CLOUD_LABEL, Cloud } from "./auth.service";

function isCloud(value: string): value is Cloud {
  return (CLOUDS as readonly string[]).includes(value);
}

function layout(body: string): string {
  return `<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Multi-cloud SSO Lab (NestJS)</title>
  <style>
    body { font-family: system-ui, sans-serif; max-width: 720px; margin: 3rem auto; padding: 0 1rem; }
    nav a { margin-right: 1rem; text-decoration: none; color: #0b5fff; }
    .card { border: 1px solid #ddd; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; }
    .btn { display: inline-block; padding: 0.5rem 1rem; border-radius: 6px; background: #111; color: #fff; text-decoration: none; margin-right: 0.5rem; }
    pre { background: #f6f6f6; padding: 1rem; border-radius: 6px; overflow-x: auto; }
  </style>
</head>
<body>
  <header>
    <a href="/"><strong>Multi-cloud SSO Lab (NestJS)</strong></a>
    <nav><a href="/aws">AWS</a><a href="/azure">Azure</a><a href="/gcp">GCP</a></nav>
  </header>
  ${body}
</body>
</html>`;
}

@Controller()
export class AppController {
  constructor(private readonly auth: AuthService) {}

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

  @Get("/:cloud")
  publicView(@Param("cloud") cloud: string, @Req() req: Request, @Res() res: Response) {
    if (!isCloud(cloud)) return res.redirect("/");

    const session = req.session as any;
    const label = CLOUD_LABEL[cloud];
    const user = session[`user_${cloud}`];
    const body = user
      ? `<p>Sesion iniciada en ${label} como <strong>${user.email || user.username || "usuario"}</strong>.</p>
         <a class="btn" href="/${cloud}/private">Vista privada</a>
         <a class="btn" href="/auth/${cloud}/logout">Cerrar sesion</a>`
      : `<p>Esta pagina es publica, no requiere autenticacion.</p>
         <a class="btn" href="/auth/${cloud}/login">Iniciar sesion con ${label}</a>`;

    res.send(layout(`<h1>${label}</h1>${body}`));
  }

  @Get("/:cloud/private")
  privateView(@Param("cloud") cloud: string, @Req() req: Request, @Res() res: Response) {
    if (!isCloud(cloud)) return res.redirect("/");

    const session = req.session as any;
    const user = session[`user_${cloud}`];
    if (!user) return res.redirect(`/auth/${cloud}/login`);

    res.send(
      layout(`<h1>${CLOUD_LABEL[cloud]}</h1><pre>${JSON.stringify(user, null, 2)}</pre>
      <a class="btn" href="/auth/${cloud}/logout">Cerrar sesion</a>`)
    );
  }

  @Get("/auth/:cloud/login")
  login(@Param("cloud") cloud: string, @Req() req: Request, @Res() res: Response) {
    if (!isCloud(cloud)) return res.redirect("/");

    const state = Math.random().toString(36).slice(2);
    (req.session as any)[`state_${cloud}`] = state;
    res.redirect(this.auth.authorizationUrl(cloud, state));
  }

  @Get("/auth/:cloud/callback")
  async callback(@Param("cloud") cloud: string, @Req() req: Request, @Res() res: Response) {
    if (!isCloud(cloud)) return res.redirect("/");

    try {
      const session = req.session as any;
      const expectedState = session[`state_${cloud}`];
      const user = await this.auth.handleCallback(cloud, req.query as Record<string, unknown>, expectedState);
      session[`user_${cloud}`] = user;
      res.redirect(`/${cloud}/private`);
    } catch (err) {
      res.status(500).send(layout(`<pre>${String(err)}</pre>`));
    }
  }

  @Get("/auth/:cloud/logout")
  logout(@Param("cloud") cloud: string, @Req() req: Request, @Res() res: Response) {
    if (isCloud(cloud)) delete (req.session as any)[`user_${cloud}`];
    res.redirect(`/${cloud}`);
  }
}
