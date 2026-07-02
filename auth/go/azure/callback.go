package azure

import (
	"encoding/json"
	"io"
	"net/http"

	"authlab/shared"
)

func Callback(w http.ResponseWriter, r *http.Request) {
	sid := shared.Sessions.GetOrCreate(w, r)
	expectedState, _ := shared.Sessions.Get(sid, "state_azure")
	if r.URL.Query().Get("state") != expectedState {
		http.Error(w, "state invalido", http.StatusBadRequest)
		return
	}

	code := r.URL.Query().Get("code")
	token, err := cfg.Exchange(r.Context(), code)
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}

	req, _ := http.NewRequestWithContext(r.Context(), http.MethodGet, userInfoURL, nil)
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

	shared.Sessions.Set(sid, "user_azure", claims)
	http.Redirect(w, r, "/azure/private", http.StatusFound)
}
