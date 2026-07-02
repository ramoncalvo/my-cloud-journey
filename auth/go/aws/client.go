package aws

import (
	"golang.org/x/oauth2"

	"authlab/shared"
)

// cfg y userInfoURL se construyen una sola vez al arrancar el proceso
// (init), igual que en las demas implementaciones del lab: nada de
// llamadas de red en este paquete hasta que un usuario hace login.
var cfg *oauth2.Config
var userInfoURL string

func init() {
	baseURL := shared.Env("BASE_URL", "http://localhost:8005")
	domain := shared.Env("AWS_COGNITO_DOMAIN", "")
	// El documento OIDC de Cognito no expone authorization_endpoint ni
	// token_endpoint (viven bajo el dominio del Hosted UI), asi que se
	// configuran a mano en vez de un discovery document.
	userInfoURL = domain + "/oauth2/userInfo"
	cfg = &oauth2.Config{
		ClientID:     shared.Env("AWS_COGNITO_CLIENT_ID", ""),
		ClientSecret: shared.Env("AWS_COGNITO_CLIENT_SECRET", ""),
		RedirectURL:  baseURL + "/auth/aws/callback",
		Scopes:       []string{"openid", "email", "profile"},
		Endpoint: oauth2.Endpoint{
			AuthURL:  domain + "/oauth2/authorize",
			TokenURL: domain + "/oauth2/token",
		},
	}
}
