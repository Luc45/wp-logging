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

phpcs: ## Run code style checks using phpcs.
	$(call execPhpAlpine,/app/vendor/bin/phpcs /app/src -s --standard=/app/.phpcs.xml.dist)

phpcbf: ## Run code style fixes using phpcbf.
	$(call execPhpAlpine,/app/vendor/bin/phpcbf /app/src -s --standard=/app/.phpcs.xml.dist)

phpstan:
	$(call execPhpAlpine,/app/vendor/bin/phpstan -vvv analyse -c /app/phpstan.neon)

phpunit: ## Run phpunit tests on both plugins or just one of them.
	@$(MAKE) setup_unit
	$(call execPhpAlpine,/var/www/html/wp-content/plugins/cd-manager,php -d memory_limit=1G $(XDEBUG) ./vendor/bin/phpunit --testsuite CD_Manager $(ARGS))

setup_unit: # Setup the WordPress test site for unit tests.
	$(call execPhpAlpine,/var/www/html/wp-content/plugins/cd-manager,./bin/install-wp-tests.sh cd_manager_test root root cd_db latest /var/www/html/wp-content/plugins/cd-manager/tests/tmp false ${CD_DEVELOPMENT_SITE_INSTALL_PORT})