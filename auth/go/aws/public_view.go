package aws

import (
	"fmt"
	"net/http"

	"authlab/shared"
)

func PublicView(w http.ResponseWriter, r *http.Request) {
	sid := shared.Sessions.GetOrCreate(w, r)
	user, ok := shared.Sessions.Get(sid, "user_aws")

	var body string
	if ok {
		claims, _ := user.(map[string]any)
		email, _ := claims["email"].(string)
		if email == "" {
			email = "usuario"
		}
		body = fmt.Sprintf(`<p>Sesion iniciada en AWS como <strong>%s</strong>.</p>
			<a class="btn" href="/aws/private">Vista privada</a>
			<a class="btn" href="/auth/aws/logout">Cerrar sesion</a>`, email)
	} else {
		body = `<p>Esta pagina es publica, no requiere autenticacion.</p>
			<a class="btn" href="/auth/aws/login">Iniciar sesion con AWS</a>`
	}
	fmt.Fprint(w, shared.Layout(fmt.Sprintf("<h1>AWS</h1>%s", body)))
}
