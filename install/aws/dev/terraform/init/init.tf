locals {
    environment = "dev"
    region = "us-east-2"
}

terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 3.0"
    }
  }
}
# Configure the AWS Provider
provider "aws" {
  region = local.region
}

resource "aws_ssm_parameter" "API_ECR_URL" {
  name  = "wikijump-${local.environment}-API_ECR_URL"
  type  = "String"
  value = aws_ecr_repository.php_ecr.repository_url
}

resource "aws_ssm_parameter" "PHP_ECR_URL" {
  name  = "wikijump-${local.environment}-PHP_ECR_URL"
  type  = "String"
  value = aws_ecr_repository.php_ecr.repository_url
}

resource "aws_ssm_parameter" "NGINX_ECR_URL" {
  name  = "wikijump-${local.environment}-NGINX_ECR_URL"
  type  = "String"
  value = aws_ecr_repository.nginx_ecr.repository_url
}

resource "aws_ssm_parameter" "DB_ECR_URL" {
  name  = "wikijump-${local.environment}-DB_ECR_URL"
  type  = "String"
  value = aws_ecr_repository.db_ecr.repository_url
}

resource "aws_ecr_repository" "api_ecr" {
  name = "wikijump-${local.environment}/api"
  encryption_configuration {
    encryption_type = "KMS"
  }
  image_scanning_configuration {
    scan_on_push = true
  }
}

resource "aws_ecr_repository" "php_ecr" {
  name = "wikijump-${local.environment}/php-fpm"
  encryption_configuration {
    encryption_type = "KMS"
  }
  image_scanning_configuration {
    scan_on_push = true
  }
}

resource "aws_ecr_repository" "nginx_ecr" {
  name = "wikijump-${local.environment}/nginx"
  encryption_configuration {
    encryption_type = "KMS"
  }
  image_scanning_configuration {
    scan_on_push = true
  }
}

resource "aws_ecr_repository" "db_ecr" {
  name = "wikijump-${local.environment}/postgres"
  encryption_configuration {
    encryption_type = "KMS"
  }
  image_scanning_configuration {
    scan_on_push = true
  }
}

resource "aws_efs_file_system" "traefik_efs" {
  creation_token = "traefik-certstore-${local.environment}"
  encrypted      = true
}

resource "aws_ssm_parameter" "TRAEFIK_EFS_ID" {
  name  = "wikijump-${local.environment}-TRAEFIK_EFS_ID"
  type  = "String"
  value = aws_efs_file_system.traefik_efs.id
}
