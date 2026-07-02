output "aws_cognito_region" {
  value = var.aws_region
}

output "aws_cognito_user_pool_id" {
  value = aws_cognito_user_pool.this.id
}

output "aws_cognito_client_id" {
  value = aws_cognito_user_pool_client.this.id
}

output "aws_cognito_client_secret" {
  value     = aws_cognito_user_pool_client.this.client_secret
  sensitive = true
}

output "aws_cognito_domain" {
  value = "https://${aws_cognito_user_pool_domain.this.domain}.auth.${var.aws_region}.amazoncognito.com"
}

output "azure_tenant_id" {
  value = data.azuread_client_config.current.tenant_id
}

output "azure_client_id" {
  value = azuread_application.this.client_id
}

output "azure_client_secret" {
  value     = azuread_application_password.this.value
  sensitive = true
}
