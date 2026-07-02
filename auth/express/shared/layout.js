function layout(body) {
  return `<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Multi-cloud SSO Lab (Express)</title>
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
    <a href="/"><strong>Multi-cloud SSO Lab (Express)</strong></a>
    <nav><a href="/aws">AWS</a><a href="/azure">Azure</a><a href="/gcp">GCP</a></nav>
  </header>
  ${body}
</body>
</html>`;
}

module.exports = { layout };
