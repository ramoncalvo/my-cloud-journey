variable "aws_region" {
  description = "Region de AWS donde crear el Cognito User Pool"
  type        = string
  default     = "eu-west-1"
}

variable "azure_app_display_name" {
  description = "Nombre visible del App Registration en Microsoft Entra ID"
  type        = string
  default     = "multi-cloud-sso-lab"
}

# Un callback/redirect URI por cada uno de los 7 stacks del lab (ver
# auth/SETUP.md). Spring Boot usa /login/oauth2/code/{id} por convencion
# de Spring Security; el resto usa /auth/{cloud}/callback.
variable "aws_callback_urls" {
  description = "Callback URLs a registrar en el App Client de Cognito"
  type        = list(string)
  default = [
    "http://localhost:8000/auth/aws/callback",      # python
    "http://localhost:8001/auth/aws/callback",      # csharp
    "http://localhost:8002/login/oauth2/code/aws",  # springboot
    "http://localhost:8003/auth/aws/callback",      # nestjs
    "http://localhost:8004/auth/aws/callback",      # express
    "http://localhost:8005/auth/aws/callback",      # go
    "http://localhost:8006/auth/aws/callback",      # php
  ]
}

variable "aws_signout_urls" {
  description = "Sign-out URLs a registrar en el App Client de Cognito"
  type        = list(string)
  default = [
    "http://localhost:8000/aws",
    "http://localhost:8001/aws",
    "http://localhost:8002/aws",
    "http://localhost:8003/aws",
    "http://localhost:8004/aws",
    "http://localhost:8005/aws",
    "http://localhost:8006/aws",
  ]
}

variable "azure_redirect_uris" {
  description = "Redirect URIs a registrar en el App Registration de Azure AD"
  type        = list(string)
  default = [
    "http://localhost:8000/auth/azure/callback",       # python
    "http://localhost:8001/auth/azure/callback",       # csharp
    "http://localhost:8002/login/oauth2/code/azure",   # springboot
    "http://localhost:8003/auth/azure/callback",       # nestjs
    "http://localhost:8004/auth/azure/callback",       # express
    "http://localhost:8005/auth/azure/callback",       # go
    "http://localhost:8006/auth/azure/callback",       # php
  ]
}
