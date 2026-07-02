.PHONY: init plan apply destroy outputs

init:
	terraform init

plan: init
	terraform plan

apply: init
	terraform apply

destroy:
	terraform destroy

outputs:
	@echo "AWS_COGNITO_REGION=$$(terraform output -raw aws_cognito_region)"
	@echo "AWS_COGNITO_USER_POOL_ID=$$(terraform output -raw aws_cognito_user_pool_id)"
	@echo "AWS_COGNITO_CLIENT_ID=$$(terraform output -raw aws_cognito_client_id)"
	@echo "AWS_COGNITO_CLIENT_SECRET=$$(terraform output -raw aws_cognito_client_secret)"
	@echo "AWS_COGNITO_DOMAIN=$$(terraform output -raw aws_cognito_domain)"
	@echo "AZURE_TENANT_ID=$$(terraform output -raw azure_tenant_id)"
	@echo "AZURE_CLIENT_ID=$$(terraform output -raw azure_client_id)"
	@echo "AZURE_CLIENT_SECRET=$$(terraform output -raw azure_client_secret)"
