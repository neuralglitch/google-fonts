.PHONY: help build shell test test-coverage install update
.PHONY: phpstan psalm infection metrics rector rector-fix
.PHONY: cs-fix cs-check qa qa-full

PHP_VERSION ?= 8.1

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-20s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

# Docker
build: ## Build Docker image
	docker compose build --build-arg PHP_VERSION=$(PHP_VERSION) 2>&1

shell: ## Open shell in Docker container
	docker compose run --rm php bash 2>&1

# Dependencies
install: ## Install composer dependencies
	docker compose run --rm php composer install 2>&1

update: ## Update composer dependencies
	docker compose run --rm php composer update 2>&1

# Testing
test: ## Run PHPUnit tests
	docker compose run --rm php composer test 2>&1

test-coverage: ## Run tests with code coverage
	XDEBUG_ENABLED=1 docker compose run --rm -e XDEBUG_MODE=coverage php composer test:coverage 2>&1

# Static Analysis
phpstan: ## Run PHPStan static analysis
	docker compose run --rm php composer phpstan 2>&1

psalm: ## Run Psalm static analysis
	docker compose run --rm php composer psalm 2>&1

psalm-baseline: ## Generate Psalm baseline
	docker compose run --rm php composer psalm:baseline 2>&1

# Mutation Testing
infection: ## Run Infection mutation testing
	XDEBUG_ENABLED=1 docker compose run --rm -e XDEBUG_MODE=coverage php composer infection 2>&1

infection-show: ## Show mutations without running tests
	docker compose run --rm php composer infection:show 2>&1

# Code Quality
metrics: ## Generate code quality metrics
	docker compose run --rm php composer metrics 2>&1

rector: ## Check for potential refactorings
	docker compose run --rm php composer rector 2>&1

rector-fix: ## Apply automated refactorings
	docker compose run --rm php composer rector:fix 2>&1

# Code Style
cs-fix: ## Fix code style issues
	docker compose run --rm php composer cs-fix 2>&1

cs-check: ## Check code style without fixing
	docker compose run --rm php composer cs-check 2>&1

# Quality Assurance
qa: ## Run quick QA checks (CS + PHPStan + Psalm + Tests)
	docker compose run --rm php composer qa 2>&1

qa-full: ## Run full QA suite (QA + Infection + Metrics)
	XDEBUG_ENABLED=1 docker compose run --rm -e XDEBUG_MODE=coverage php composer qa:full 2>&1

