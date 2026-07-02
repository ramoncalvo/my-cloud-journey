package gcp

import (
	"net/http"

	"authlab/shared"
)

func Login(w http.ResponseWriter, r *http.Request) {
	sid := shared.Sessions.GetOrCreate(w, r)
	state := shared.RandomID()
	shared.Sessions.Set(sid, "state_gcp", state)
	http.Redirect(w, r, cfg.AuthCodeURL(state), http.StatusFound)
}
