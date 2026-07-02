package main

import (
	"fmt"
	"log"
	"net/http"

	"authlab/aws"
	"authlab/azure"
	"authlab/gcp"
	"authlab/shared"
)

// Screaming architecture: cada nube es su propio paquete Go (aws/,
// azure/, gcp/) con sus 6 archivos (client, login, callback, logout,
// public_view, private_view). main.go no tiene logica de negocio, solo
// registra cada handler en su ruta.
func main() {
	mux := http.NewServeMux()

	mux.HandleFunc("GET /{$}", func(w http.ResponseWriter, r *http.Request) {
		fmt.Fprint(w, shared.Layout(`
			<p>Version Go vanilla del lab. Cada nube tiene vista publica y privada por SSO (OIDC).</p>
			<div class="card"><h2>AWS</h2><a href="/aws">Vista publica</a> &middot; <a href="/aws/private">Vista privada</a></div>
			<div class="card"><h2>Azure</h2><a href="/azure">Vista publica</a> &middot; <a href="/azure/private">Vista privada</a></div>
			<div class="card"><h2>Google Cloud</h2><a href="/gcp">Vista publica</a> &middot; <a href="/gcp/private">Vista privada</a></div>
		`))
	})

	mux.HandleFunc("GET /aws", aws.PublicView)
	mux.HandleFunc("GET /aws/private", aws.PrivateView)
	mux.HandleFunc("GET /auth/aws/login", aws.Login)
	mux.HandleFunc("GET /auth/aws/callback", aws.Callback)
	mux.HandleFunc("GET /auth/aws/logout", aws.Logout)

	mux.HandleFunc("GET /azure", azure.PublicView)
	mux.HandleFunc("GET /azure/private", azure.PrivateView)
	mux.HandleFunc("GET /auth/azure/login", azure.Login)
	mux.HandleFunc("GET /auth/azure/callback", azure.Callback)
	mux.HandleFunc("GET /auth/azure/logout", azure.Logout)

	mux.HandleFunc("GET /gcp", gcp.PublicView)
	mux.HandleFunc("GET /gcp/private", gcp.PrivateView)
	mux.HandleFunc("GET /auth/gcp/login", gcp.Login)
	mux.HandleFunc("GET /auth/gcp/callback", gcp.Callback)
	mux.HandleFunc("GET /auth/gcp/logout", gcp.Logout)

	log.Println("Go SSO lab listening on 8005")
	log.Fatal(http.ListenAndServe(":8005", mux))
}
