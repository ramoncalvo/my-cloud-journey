package dev.awslab.sso;

import jakarta.servlet.http.HttpSession;
import org.springframework.http.HttpHeaders;
import org.springframework.http.HttpStatus;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RestController;

import java.util.LinkedHashMap;
import java.util.Map;
import java.util.Set;

/**
 * Mismas rutas que las versiones Python y C# del lab, para poder comparar
 * los 3 frameworks: publicas (/{cloud}) y privadas (/{cloud}/private), mas
 * /auth/{cloud}/login y /auth/{cloud}/logout. El callback OIDC en cambio
 * usa la convencion propia de Spring Security (/login/oauth2/code/{id}),
 * gestionada automaticamente por el filtro de oauth2Login (ver SecurityConfig).
 */
@RestController
public class ViewController {

    private static final Set<String> CLOUDS = Set.of("aws", "azure", "gcp");

    private static String label(String cloud) {
        return switch (cloud) {
            case "aws" -> "AWS";
            case "azure" -> "Azure";
            case "gcp" -> "Google Cloud";
            default -> cloud;
        };
    }

    // El registrationId configurado en application.yml para Google es
    // "google", no "gcp"; esta funcion traduce nube -> registrationId.
    private static String registrationId(String cloud) {
        return "gcp".equals(cloud) ? "google" : cloud;
    }

    @GetMapping(value = "/", produces = MediaType.TEXT_HTML_VALUE)
    public String index() {
        return layout("""
            <p>Version Spring Boot del lab. Cada nube tiene vista publica y privada por SSO (OIDC).</p>
            <div class="card"><h2>AWS</h2><a href="/aws">Vista publica</a> &middot; <a href="/aws/private">Vista privada</a></div>
            <div class="card"><h2>Azure</h2><a href="/azure">Vista publica</a> &middot; <a href="/azure/private">Vista privada</a></div>
            <div class="card"><h2>Google Cloud</h2><a href="/gcp">Vista publica</a> &middot; <a href="/gcp/private">Vista privada</a></div>
            """);
    }

    @GetMapping("/{cloud}")
    public ResponseEntity<String> publicView(@PathVariable String cloud, HttpSession session) {
        if (!CLOUDS.contains(cloud)) {
            return redirect("/");
        }

        @SuppressWarnings("unchecked")
        Map<String, Object> user = (Map<String, Object>) session.getAttribute("user_" + cloud);
        String label = label(cloud);

        String body;
        if (user != null) {
            Object email = user.getOrDefault("email", user.getOrDefault("username", "usuario"));
            body = """
                <p>Sesion iniciada en %s como <strong>%s</strong>.</p>
                <a class="btn" href="/%s/private">Vista privada</a>
                <a class="btn" href="/auth/%s/logout">Cerrar sesion</a>
                """.formatted(label, email, cloud, cloud);
        } else {
            body = """
                <p>Esta pagina es publica, no requiere autenticacion.</p>
                <a class="btn" href="/auth/%s/login">Iniciar sesion con %s</a>
                """.formatted(cloud, label);
        }

        return html(layout("<h1>" + label + "</h1>" + body));
    }

    @GetMapping("/{cloud}/private")
    public ResponseEntity<String> privateView(@PathVariable String cloud, HttpSession session) {
        if (!CLOUDS.contains(cloud)) {
            return redirect("/");
        }

        Object user = session.getAttribute("user_" + cloud);
        if (user == null) {
            return redirect("/auth/" + cloud + "/login");
        }

        String body = "<pre>" + user + "</pre><a class=\"btn\" href=\"/auth/" + cloud + "/logout\">Cerrar sesion</a>";
        return html(layout("<h1>" + label(cloud) + "</h1>" + body));
    }

    @GetMapping("/auth/{cloud}/login")
    public ResponseEntity<String> login(@PathVariable String cloud) {
        if (!CLOUDS.contains(cloud)) {
            return redirect("/");
        }
        // Spring Security expone el endpoint de login OAuth2 en
        // /oauth2/authorization/{registrationId}; solo redirigimos ahi.
        return redirect("/oauth2/authorization/" + registrationId(cloud));
    }

    @GetMapping("/auth/{cloud}/logout")
    public ResponseEntity<String> logout(@PathVariable String cloud, HttpSession session) {
        if (CLOUDS.contains(cloud)) {
            session.removeAttribute("user_" + cloud);
        }
        return redirect("/" + cloud);
    }

    private static ResponseEntity<String> redirect(String location) {
        return ResponseEntity.status(HttpStatus.FOUND).header(HttpHeaders.LOCATION, location).build();
    }

    private static ResponseEntity<String> html(String body) {
        return ResponseEntity.ok().contentType(MediaType.TEXT_HTML).body(body);
    }

    private static String layout(String body) {
        return """
            <!doctype html>
            <html lang="es">
            <head>
              <meta charset="utf-8">
              <title>Multi-cloud SSO Lab (Spring Boot)</title>
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
                <a href="/"><strong>Multi-cloud SSO Lab (Spring Boot)</strong></a>
                <nav><a href="/aws">AWS</a><a href="/azure">Azure</a><a href="/gcp">GCP</a></nav>
              </header>
              %s
            </body>
            </html>
            """.formatted(body);
    }
}
