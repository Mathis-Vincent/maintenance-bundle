PHP = docker compose run --rm php

.PHONY: install test coverage cs cs-fix psalm ci shell

install: ## Build the image and install dependencies
	docker compose build
	$(PHP) composer install

test: ## Run the test suite
	$(PHP) vendor/bin/phpunit

coverage: ## Run the tests with an HTML coverage report in coverage/
	$(PHP) vendor/bin/phpunit --coverage-html coverage --coverage-text

cs: ## Check the coding style
	$(PHP) vendor/bin/php-cs-fixer fix --dry-run --diff

cs-fix: ## Fix the coding style
	$(PHP) vendor/bin/php-cs-fixer fix

psalm: ## Run static analysis
	$(PHP) vendor/bin/psalm --no-cache

ci: cs psalm test ## Run every check, like the CI

shell: ## Open a shell in the PHP container
	$(PHP) bash
