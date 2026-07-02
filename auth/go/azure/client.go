package azure

import (
	"golang.org/x/oauth2"

	"authlab/shared"
)

var cfg *oauth2.Config
var userInfoURL string

func init() {
	baseURL := shared.Env("BASE_URL", "http://localhost:8005")
	tenant := shared.Env("AZURE_TENANT_ID", "common")
	userInfoURL = "https://graph.microsoft.com/oidc/userinfo"
	cfg = &oauth2.Config{
		ClientID:     shared.Env("AZURE_CLIENT_ID", ""),
		ClientSecret: shared.Env("AZURE_CLIENT_SECRET", ""),
		RedirectURL:  baseURL + "/auth/azure/callback",
		Scopes:       []string{"openid", "email", "profile"},
		Endpoint: oauth2.Endpoint{
			AuthURL:  "https://login.microsoftonline.com/" + tenant + "/oauth2/v2.0/authorize",
			TokenURL: "https://login.microsoftonline.com/" + tenant + "/oauth2/v2.0/token",
		},
	}
}
