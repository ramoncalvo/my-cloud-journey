package gcp

import (
	"encoding/json"
	"fmt"
	"net/http"

	"authlab/shared"
)

func PrivateView(w http.ResponseWriter, r *http.Request) {
	sid := shared.Sessions.GetOrCreate(w, r)
	user, ok := shared.Sessions.Get(sid, "user_gcp")
	if !ok {
		http.Redirect(w, r, "/auth/gcp/login", http.StatusFound)
		return
	}
	pretty, _ := json.MarshalIndent(user, "", "  ")
	body := fmt.Sprintf(`<pre>%s</pre><a class="btn" href="/auth/gcp/logout">Cerrar sesion</a>`, string(pretty))
	fmt.Fprint(w, shared.Layout(fmt.Sprintf("<h1>Google Cloud</h1>%s", body)))
}
