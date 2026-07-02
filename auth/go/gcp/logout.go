package gcp

import (
	"net/http"

	"authlab/shared"
)

func Logout(w http.ResponseWriter, r *http.Request) {
	sid := shared.Sessions.GetOrCreate(w, r)
	shared.Sessions.Delete(sid, "user_gcp")
	http.Redirect(w, r, "/gcp", http.StatusFound)
}
