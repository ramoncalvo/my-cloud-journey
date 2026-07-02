data "azuread_client_config" "current" {}

resource "azuread_application" "this" {
  display_name = var.azure_app_display_name

  web {
    redirect_uris = var.azure_redirect_uris

    implicit_grant {
      access_token_issuance_enabled = false
      id_token_issuance_enabled     = false
    }
  }

  required_resource_access {
    resource_app_id = "00000003-0000-0000-c000-000000000000" # Microsoft Graph

    resource_access {
      id   = "e1fe6dd8-ba31-4d61-89e7-88639da4683d" # User.Read (delegated scope)
      type = "Scope"
    }
  }
}

resource "azuread_application_password" "this" {
  application_id = azuread_application.this.id
  display_name   = "multi-cloud-sso-lab-secret"
}
