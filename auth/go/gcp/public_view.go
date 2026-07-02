package gcp

import (
	"fmt"
	"net/http"

	"authlab/shared"
)

func PublicView(w http.ResponseWriter, r *http.Request) {
	sid := shared.Sessions.GetOrCreate(w, r)
	user, ok := shared.Sessions.Get(sid, "user_gcp")

	var body string
	if ok {
		claims, _ := user.(map[string]any)
		email, _ := claims["email"].(string)
		if email == "" {
			email = "usuario"
		}
		body = fmt.Sprintf(`<p>Sesion iniciada en Google Cloud como <strong>%s</strong>.</p>
			<a class="btn" href="/gcp/private">Vista privada</a>
			<a class="btn" href="/auth/gcp/logout">Cerrar sesion</a>`, email)
	} else {
		body = `<p>Esta pagina es publica, no requiere autenticacion.</p>
			<a class="btn" href="/auth/gcp/login">Iniciar sesion con Google Cloud</a>`
	}
	fmt.Fprint(w, shared.Layout(fmt.Sprintf("<h1>Google Cloud</h1>%s", body)))
}
