package azure

import (
	"encoding/json"
	"fmt"
	"net/http"

	"authlab/shared"
)

func PrivateView(w http.ResponseWriter, r *http.Request) {
	sid := shared.Sessions.GetOrCreate(w, r)
	user, ok := shared.Sessions.Get(sid, "user_azure")
	if !ok {
		http.Redirect(w, r, "/auth/azure/login", http.StatusFound)
		return
	}
	pretty, _ := json.MarshalIndent(user, "", "  ")
	body := fmt.Sprintf(`<pre>%s</pre><a class="btn" href="/auth/azure/logout">Cerrar sesion</a>`, string(pretty))
	fmt.Fprint(w, shared.Layout(fmt.Sprintf("<h1>Azure</h1>%s", body)))
}
