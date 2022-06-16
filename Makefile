##
# Variables.
#
# ROOT       Set this to 1 to run the command as root.
# ARGS       Additional arguments to be passed to the command.
#
# Examples:
# - make enter ROOT=1 (to enter the PHP container terminal as root)
# - make phpunit ARGS=/var/www/html/wp-content/plugins/cd-manager/tests/FooTest.php (to run only FooTests)
##
ROOT ?= 0
ARGS ?=

ifeq (1, $(ROOT))
DOCKER_USER ?= "0:0"
else
DOCKER_USER ?= "$(shell id -u):$(shell id -g)"
endif

## Run a command inside the Development PHP Docker container.
## 1. Work directory 2. Command to execute
define execDevEnv
	docker compose exec --workdir "$(1)" --user $(DOCKER_USER) -T wplogging_php bash -c "$(2)"
endef

## Run a command inside an alpine PHP 7 CLI image.
## 1. Command to execute, eg: "./vendor/bin/phpcs" 2. Working dir (optional)
define execPhpAlpine
	docker run --rm \
		--user $(DOCKER_USER) \
		-v "${PWD}:/app" \
		--workdir "$(2:=/)" \
		php:7-cli \
		bash -c "php -d memory_limit=1G $(1)"
endef

## Run a command inside a Composer docker image.
## 1. Work directory 2. Command to execute, eg: "install" or "update", or "install --no-dev"
define execComposer
	docker run --rm \
		--user $(DOCKER_USER) \
		-v "${PWD}:/app" \
		-e COMPOSER_CACHE_DIR="/app/.cache/composer" \
		--workdir "$(1)" \
		composer $2
endef

up: ## Start the docker stack
	docker compose up -d

down: ## Stop the docker stack
	docker compose down

restart: down up ## Restart the docker stack

enter: ## Opens a bash shell in the running `php-fpm` container.
	docker exec -it --user $(DOCKER_USER) -w /var/wplogging wplogging_php bash

phpcs: ## Run code style checks using phpcs.
	$(call execPhpAlpine,/app/vendor/bin/phpcs /app/wp-logging/src -s --standard=/app/.phpcs.xml.dist)

phpcbf: ## Run code style fixes using phpcbf.
	$(call execPhpAlpine,/app/vendor/bin/phpcbf /app/wp-logging/src -s --standard=/app/.phpcs.xml.dist)

phpstan:
	$(call execPhpAlpine,/app/vendor/bin/phpstan -vvv analyse -c /app/phpstan.neon)

composer:
	$(call execComposer,/app,$(ARGS))

fix_autocomplete: ## Provides autocompletion for WP_CLI and WP specific PHPUnit classes.
	if [ ! -d "./dev/wordpress-develop" ]; then git clone --depth=1 --branch=master git@github.com:WordPress/wordpress-develop.git ./dev/wordpress-develop; fi;
	if [ ! -d "./dev/wp-cli" ]; then mkdir -p ./dev/wp-cli && composer --working-dir=./dev/wp-cli require wp-cli/wp-cli-bundle; fi;

reset: ## Reset the development environment.
	$(MAKE) down
	# Reset database and filesystem
	sudo rm -rf ./docker/wplogging_db/data ./tests/tmp/*
	# Re-create folders to avoid Docker permission issues.
	mkdir -p ./docker/wplogging_db/data
	mkdir -p ./tests/tmp
	# Create cache folder if it doesn't exist
	if [ ! -d "./.cache" ]; then mkdir ./.cache; fi;

setup: ## Reset/Setup the development environment.
	$(MAKE) reset
	$(MAKE) fix_autocomplete
	$(MAKE) up

	# Install Composer
	$(MAKE) composer ARGS=install

	$(MAKE) setup_unit


phpunit: ## Run unit tests.
	$(call execDevEnv,/var/wplogging,php -d memory_limit=1G $(XDEBUG) ./vendor/bin/phpunit --testsuite=WPLogging $(ARGS))

setup_unit:
	# Download and Setup WordPress
	$(call execDevEnv,/var/wplogging,./tests/bin/create_site.sh wplogging wplogging wplogging wplogging_db latest /var/wplogging/tests/tmp false)