# El dominio del Hosted UI debe ser unico globalmente en toda AWS; un
# sufijo aleatorio evita tener que pensar un nombre a mano y permite que
# "terraform apply" funcione sin pedir ninguna variable.
resource "random_id" "cognito_domain_suffix" {
  byte_length = 4
}

resource "aws_cognito_user_pool" "this" {
  name                     = "multi-cloud-sso-lab"
  username_attributes      = ["email"]
  auto_verified_attributes = ["email"]
}

resource "aws_cognito_user_pool_domain" "this" {
  domain       = "multi-cloud-sso-lab-${random_id.cognito_domain_suffix.hex}"
  user_pool_id = aws_cognito_user_pool.this.id
}

resource "aws_cognito_user_pool_client" "this" {
  name         = "multi-cloud-sso-lab"
  user_pool_id = aws_cognito_user_pool.this.id

  generate_secret                     = true
  allowed_oauth_flows_user_pool_client = true
  allowed_oauth_flows                  = ["code"]
  allowed_oauth_scopes                 = ["openid", "email", "profile"]
  supported_identity_providers         = ["COGNITO"]

  callback_urls = var.aws_callback_urls
  logout_urls   = var.aws_signout_urls
}
