package gcp

import (
	"golang.org/x/oauth2"

	"authlab/shared"
)

var cfg *oauth2.Config
var userInfoURL string

func init() {
	baseURL := shared.Env("BASE_URL", "http://localhost:8005")
	userInfoURL = "https://openidconnect.googleapis.com/v1/userinfo"
	cfg = &oauth2.Config{
		ClientID:     shared.Env("GOOGLE_CLIENT_ID", ""),
		ClientSecret: shared.Env("GOOGLE_CLIENT_SECRET", ""),
		RedirectURL:  baseURL + "/auth/gcp/callback",
		Scopes:       []string{"openid", "email", "profile"},
		Endpoint: oauth2.Endpoint{
			AuthURL:  "https://accounts.google.com/o/oauth2/v2/auth",
			TokenURL: "https://oauth2.googleapis.com/token",
		},
	}
}
