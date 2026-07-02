terraform {
  required_version = ">= 1.5"

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
    azuread = {
      source  = "hashicorp/azuread"
      version = "~> 3.0"
    }
    random = {
      source  = "hashicorp/random"
      version = "~> 3.6"
    }
  }
}

# Credenciales por variables de entorno estandar de cada CLI:
# AWS:   AWS_ACCESS_KEY_ID / AWS_SECRET_ACCESS_KEY (o `aws configure`)
# Azure: `az login` (el provider azuread reutiliza esa sesion)
provider "aws" {
  region = var.aws_region
}

provider "azuread" {}
