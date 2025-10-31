.PHONY: help build shell test test-coverage phpstan cs-fix cs-check install

PHP_VERSION ?= 8.2

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-15s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

build: ## Build Docker image
	docker compose build --build-arg PHP_VERSION=$(PHP_VERSION)

shell: ## Open shell in Docker container
	docker compose run --rm php bash

install: ## Install composer dependencies in Docker
	docker compose run --rm php composer install

test: ## Run tests in Docker
	docker compose run --rm php composer test

test-coverage: ## Run tests with code coverage in Docker
	XDEBUG_ENABLED=1 docker compose run --rm -e XDEBUG_MODE=coverage php composer test:coverage

phpstan: ## Run PHPStan in Docker
	docker compose run --rm php composer phpstan

cs-fix: ## Fix code style in Docker
	docker compose run --rm php composer cs-fix

cs-check: ## Check code style in Docker
	docker compose run --rm php composer cs-check

