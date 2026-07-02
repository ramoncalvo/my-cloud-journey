package main

import (
	"crypto/rand"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"io"
	"log"
	"net/http"
	"os"
	"sync"

	"golang.org/x/oauth2"
)

// Version Go vanilla del mismo lab que python/, csharp/, springboot/,
// express/ y nestjs/: 3 vistas publicas y 3 privadas, SSO real por OIDC
// contra cada nube. "Vanilla" aqui significa: sin framework web (net/http
// puro) y sin libreria de sesiones — la sesion es un mapa en memoria
// protegido por un mutex, indexado por una cookie de sesion propia.

var clouds = []string{"aws", "azure", "gcp"}
var cloudLabel = map[string]string{"aws": "AWS", "azure": "Azure", "gcp": "Google Cloud"}

func isCloud(c string) bool {
	for _, x := range clouds {
		if x == c {
			return true
		}
	}
	return false
}

func env(key, fallback string) string {
	if v := os.Getenv(key); v != "" {
		return v
	}
	return fallback
}

// userInfoURL guarda el endpoint OIDC userinfo de cada nube; se llama con
// el access_token tras el intercambio del "code" para obtener las claims
// de identidad, igual que en las demas implementaciones del lab.
var userInfoURL = map[string]string{}

func buildOAuthConfig(cloud, baseURL string) *oauth2.Config {
	redirectURL := fmt.Sprintf("%s/auth/%s/callback", baseURL, cloud)

	switch cloud {
	case "aws":
		domain := env("AWS_COGNITO_DOMAIN", "")
		userInfoURL["aws"] = domain + "/oauth2/userInfo"
		return &oauth2.Config{
			ClientID:     env("AWS_COGNITO_CLIENT_ID", ""),
			ClientSecret: env("AWS_COGNITO_CLIENT_SECRET", ""),
			RedirectURL:  redirectURL,
			Scopes:       []string{"openid", "email", "profile"},
			Endpoint: oauth2.Endpoint{
				AuthURL:  domain + "/oauth2/authorize",
				TokenURL: domain + "/oauth2/token",
			},
		}
	case "azure":
		tenant := env("AZURE_TENANT_ID", "common")
		userInfoURL["azure"] = "https://graph.microsoft.com/oidc/userinfo"
		return &oauth2.Config{
			ClientID:     env("AZURE_CLIENT_ID", ""),
			ClientSecret: env("AZURE_CLIENT_SECRET", ""),
			RedirectURL:  redirectURL,
			Scopes:       []string{"openid", "email", "profile"},
			Endpoint: oauth2.Endpoint{
				AuthURL:  fmt.Sprintf("https://login.microsoftonline.com/%s/oauth2/v2.0/authorize", tenant),
				TokenURL: fmt.Sprintf("https://login.microsoftonline.com/%s/oauth2/v2.0/token", tenant),
			},
		}
	default: // gcp
		userInfoURL["gcp"] = "https://openidconnect.googleapis.com/v1/userinfo"
		return &oauth2.Config{
			ClientID:     env("GOOGLE_CLIENT_ID", ""),
			ClientSecret: env("GOOGLE_CLIENT_SECRET", ""),
			RedirectURL:  redirectURL,
			Scopes:       []string{"openid", "email", "profile"},
			Endpoint: oauth2.Endpoint{
				AuthURL:  "https://accounts.google.com/o/oauth2/v2/auth",
				TokenURL: "https://oauth2.googleapis.com/token",
			},
		}
	}
}

// --- Sesion propia en memoria (sin libreria externa) ---

type sessionStore struct {
	mu   sync.Mutex
	data map[string]map[string]any
}

func newSessionStore() *sessionStore {
	return &sessionStore{data: make(map[string]map[string]any)}
}

func randomID() string {
	b := make([]byte, 16)
	_, _ = rand.Read(b)
	return hex.EncodeToString(b)
}

func (s *sessionStore) getOrCreate(w http.ResponseWriter, r *http.Request) string {
	cookie, err := r.Cookie("sid")
	if err == nil && cookie.Value != "" {
		s.mu.Lock()
		_, ok := s.data[cookie.Value]
		s.mu.Unlock()
		if ok {
			return cookie.Value
		}
	}
	id := randomID()
	s.mu.Lock()
	s.data[id] = make(map[string]any)
	s.mu.Unlock()
	http.SetCookie(w, &http.Cookie{Name: "sid", Value: id, Path: "/", HttpOnly: true})
	return id
}

func (s *sessionStore) get(id, key string) (any, bool) {
	s.mu.Lock()
	defer s.mu.Unlock()
	v, ok := s.data[id][key]
	return v, ok
}

func (s *sessionStore) set(id, key string, value any) {
	s.mu.Lock()
	defer s.mu.Unlock()
	s.data[id][key] = value
}

func (s *sessionStore) delete(id, key string) {
	s.mu.Lock()
	defer s.mu.Unlock()
	delete(s.data[id], key)
}

// --- Vistas HTML ---

func layout(body string) string {
	return fmt.Sprintf(`<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Multi-cloud SSO Lab (Go)</title>
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
    <a href="/"><strong>Multi-cloud SSO Lab (Go)</strong></a>
    <nav><a href="/aws">AWS</a><a href="/azure">Azure</a><a href="/gcp">GCP</a></nav>
  </header>
  %s
</body>
</html>`, body)
}

func main() {
	baseURL := env("BASE_URL", "http://localhost:8005")
	configs := map[string]*oauth2.Config{}
	for _, cloud := range clouds {
		configs[cloud] = buildOAuthConfig(cloud, baseURL)
	}
	sessions := newSessionStore()

	mux := http.NewServeMux()

	mux.HandleFunc("GET /{$}", func(w http.ResponseWriter, r *http.Request) {
		fmt.Fprint(w, layout(`
			<p>Version Go vanilla del lab. Cada nube tiene vista publica y privada por SSO (OIDC).</p>
			<div class="card"><h2>AWS</h2><a href="/aws">Vista publica</a> &middot; <a href="/aws/private">Vista privada</a></div>
			<div class="card"><h2>Azure</h2><a href="/azure">Vista publica</a> &middot; <a href="/azure/private">Vista privada</a></div>
			<div class="card"><h2>Google Cloud</h2><a href="/gcp">Vista publica</a> &middot; <a href="/gcp/private">Vista privada</a></div>
		`))
	})

	mux.HandleFunc("GET /{cloud}", func(w http.ResponseWriter, r *http.Request) {
		cloud := r.PathValue("cloud")
		if !isCloud(cloud) {
			http.Redirect(w, r, "/", http.StatusFound)
			return
		}
		sid := sessions.getOrCreate(w, r)
		label := cloudLabel[cloud]
		user, ok := sessions.get(sid, "user_"+cloud)

		var body string
		if ok {
			claims, _ := user.(map[string]any)
			email, _ := claims["email"].(string)
			if email == "" {
				email = "usuario"
			}
			body = fmt.Sprintf(`<p>Sesion iniciada en %s como <strong>%s</strong>.</p>
				<a class="btn" href="/%s/private">Vista privada</a>
				<a class="btn" href="/auth/%s/logout">Cerrar sesion</a>`, label, email, cloud, cloud)
		} else {
			body = fmt.Sprintf(`<p>Esta pagina es publica, no requiere autenticacion.</p>
				<a class="btn" href="/auth/%s/login">Iniciar sesion con %s</a>`, cloud, label)
		}
		fmt.Fprint(w, layout(fmt.Sprintf("<h1>%s</h1>%s", label, body)))
	})

	mux.HandleFunc("GET /{cloud}/private", func(w http.ResponseWriter, r *http.Request) {
		cloud := r.PathValue("cloud")
		if !isCloud(cloud) {
			http.Redirect(w, r, "/", http.StatusFound)
			return
		}
		sid := sessions.getOrCreate(w, r)
		user, ok := sessions.get(sid, "user_"+cloud)
		if !ok {
			http.Redirect(w, r, "/auth/"+cloud+"/login", http.StatusFound)
			return
		}
		pretty, _ := json.MarshalIndent(user, "", "  ")
		body := fmt.Sprintf(`<pre>%s</pre><a class="btn" href="/auth/%s/logout">Cerrar sesion</a>`, string(pretty), cloud)
		fmt.Fprint(w, layout(fmt.Sprintf("<h1>%s</h1>%s", cloudLabel[cloud], body)))
	})

	mux.HandleFunc("GET /auth/{cloud}/login", func(w http.ResponseWriter, r *http.Request) {
		cloud := r.PathValue("cloud")
		if !isCloud(cloud) {
			http.Redirect(w, r, "/", http.StatusFound)
			return
		}
		sid := sessions.getOrCreate(w, r)
		state := randomID()
		sessions.set(sid, "state_"+cloud, state)
		http.Redirect(w, r, configs[cloud].AuthCodeURL(state), http.StatusFound)
	})

	mux.HandleFunc("GET /auth/{cloud}/callback", func(w http.ResponseWriter, r *http.Request) {
		cloud := r.PathValue("cloud")
		if !isCloud(cloud) {
			http.Redirect(w, r, "/", http.StatusFound)
			return
		}
		sid := sessions.getOrCreate(w, r)
		expectedState, _ := sessions.get(sid, "state_"+cloud)
		if r.URL.Query().Get("state") != expectedState {
			http.Error(w, "state invalido", http.StatusBadRequest)
			return
		}

		code := r.URL.Query().Get("code")
		token, err := configs[cloud].Exchange(r.Context(), code)
		if err != nil {
			http.Error(w, err.Error(), http.StatusInternalServerError)
			return
		}

		// Igual que en las otras implementaciones: se pide el userinfo
		// endpoint con el access_token en vez de decodificar el id_token.
		req, _ := http.NewRequestWithContext(r.Context(), http.MethodGet, userInfoURL[cloud], nil)
		req.Header.Set("Authorization", "Bearer "+token.AccessToken)
		resp, err := http.DefaultClient.Do(req)
		if err != nil {
			http.Error(w, err.Error(), http.StatusInternalServerError)
			return
		}
		defer resp.Body.Close()
		raw, _ := io.ReadAll(resp.Body)

		var claims map[string]any
		if err := json.Unmarshal(raw, &claims); err != nil {
			http.Error(w, "respuesta userinfo invalida: "+string(raw), http.StatusInternalServerError)
			return
		}

		sessions.set(sid, "user_"+cloud, claims)
		http.Redirect(w, r, "/"+cloud+"/private", http.StatusFound)
	})

	mux.HandleFunc("GET /auth/{cloud}/logout", func(w http.ResponseWriter, r *http.Request) {
		cloud := r.PathValue("cloud")
		sid := sessions.getOrCreate(w, r)
		if isCloud(cloud) {
			sessions.delete(sid, "user_"+cloud)
		}
		http.Redirect(w, r, "/"+cloud, http.StatusFound)
	})

	log.Println("Go SSO lab listening on 8005")
	log.Fatal(http.ListenAndServe(":8005", mux))
}
