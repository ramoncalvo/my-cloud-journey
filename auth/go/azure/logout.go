package azure

import (
	"net/http"

	"authlab/shared"
)

func Logout(w http.ResponseWriter, r *http.Request) {
	sid := shared.Sessions.GetOrCreate(w, r)
	shared.Sessions.Delete(sid, "user_azure")
	http.Redirect(w, r, "/azure", http.StatusFound)
}
