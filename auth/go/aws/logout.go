package aws

import (
	"net/http"

	"authlab/shared"
)

func Logout(w http.ResponseWriter, r *http.Request) {
	sid := shared.Sessions.GetOrCreate(w, r)
	shared.Sessions.Delete(sid, "user_aws")
	http.Redirect(w, r, "/aws", http.StatusFound)
}
