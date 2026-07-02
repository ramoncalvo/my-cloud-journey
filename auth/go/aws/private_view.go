package aws

import (
	"encoding/json"
	"fmt"
	"net/http"

	"authlab/shared"
)

func PrivateView(w http.ResponseWriter, r *http.Request) {
	sid := shared.Sessions.GetOrCreate(w, r)
	user, ok := shared.Sessions.Get(sid, "user_aws")
	if !ok {
		http.Redirect(w, r, "/auth/aws/login", http.StatusFound)
		return
	}
	pretty, _ := json.MarshalIndent(user, "", "  ")
	body := fmt.Sprintf(`<pre>%s</pre><a class="btn" href="/auth/aws/logout">Cerrar sesion</a>`, string(pretty))
	fmt.Fprint(w, shared.Layout(fmt.Sprintf("<h1>AWS</h1>%s", body)))
}
