# Dev environment for local_btcrewards using moodle-docker.
#
# Layout (everything under .dev/ is gitignored):
#   .dev/moodle-docker/   clone of moodlehq/moodle-docker
#   .dev/moodle/          clone of moodle/moodle (MOODLE_DOCKER_WWWROOT)
#
# The plugin is bind-mounted into the webserver container via local.yml,
# which moodle-docker auto-loads alongside its base compose files.
#
# Usage:
#   make init           # one-time: clone moodle-docker + moodle, link plugin
#   make up             # start containers
#   make install        # run Moodle site installer inside the container
#   make shell          # bash into the webserver container
#   make logs           # tail webserver logs
#   make cli CMD="admin/cli/purge_caches.php"
#   make phpunit-init   # initialise the PHPUnit test environment
#   make phpunit        # run this plugin's PHPUnit tests
#   make down           # stop containers (keep volumes)
#   make purge          # stop containers AND drop volumes

SHELL := /bin/bash

DEV_DIR            := $(CURDIR)/.dev
MOODLE_DOCKER_DIR  := $(DEV_DIR)/moodle-docker
MOODLE_SRC_DIR     := $(DEV_DIR)/moodle
PLUGIN_SRC_DIR     := $(CURDIR)
LOCAL_YML          := $(DEV_DIR)/local.yml

MOODLE_BRANCH      ?= MOODLE_404_STABLE
MOODLE_DOCKER_DB   ?= pgsql
MOODLE_DOCKER_PHP_VERSION ?= 8.1

DC := MOODLE_DOCKER_WWWROOT=$(MOODLE_SRC_DIR) \
      MOODLE_DOCKER_DB=$(MOODLE_DOCKER_DB) \
      MOODLE_DOCKER_PHP_VERSION=$(MOODLE_DOCKER_PHP_VERSION) \
      $(MOODLE_DOCKER_DIR)/bin/moodle-docker-compose -f $(LOCAL_YML)

EXEC_WEB := $(DC) exec -T webserver

.PHONY: help init up down purge install shell logs cli phpunit-init phpunit status

help:
	@grep -E '^[a-zA-Z_-]+:.*?##' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "} {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}'

init: $(MOODLE_DOCKER_DIR) $(MOODLE_SRC_DIR) $(LOCAL_YML) ## Clone moodle-docker + moodle, render local.yml override

$(DEV_DIR):
	mkdir -p $(DEV_DIR)

$(MOODLE_DOCKER_DIR): | $(DEV_DIR)
	git clone --depth 1 https://github.com/moodlehq/moodle-docker.git $(MOODLE_DOCKER_DIR)

$(MOODLE_SRC_DIR): | $(DEV_DIR)
	git clone --depth 1 --branch $(MOODLE_BRANCH) https://github.com/moodle/moodle.git $(MOODLE_SRC_DIR)
	cp $(MOODLE_DOCKER_DIR)/config.docker-template.php $(MOODLE_SRC_DIR)/config.php

$(LOCAL_YML): local.yml | $(DEV_DIR)
	sed 's|__PLUGIN_ABS_PATH__|$(PLUGIN_SRC_DIR)|g' local.yml > $(LOCAL_YML)

up: init ## Start containers (detached)
	$(DC) up -d
	@echo "Moodle will be available at http://localhost:8000 once install completes."

down: ## Stop containers (keep volumes)
	$(DC) down

purge: ## Stop containers and drop volumes (fresh DB on next up)
	$(DC) down -v

status: ## Show container status
	$(DC) ps

install: ## Run the Moodle installer inside the webserver container
	$(EXEC_WEB) php admin/cli/install_database.php \
		--agree-license \
		--fullname="BTC Rewards Dev" \
		--shortname="btcdev" \
		--summary="Local dev site for local_btcrewards" \
		--adminpass="Admin*123" \
		--adminemail="admin@example.com"

shell: ## Bash into the webserver container
	$(DC) exec webserver bash

logs: ## Tail webserver logs
	$(DC) logs -f webserver

cli: ## Run a Moodle CLI script: make cli CMD="admin/cli/purge_caches.php"
	@if [ -z "$(CMD)" ]; then echo "Usage: make cli CMD=\"admin/cli/purge_caches.php\""; exit 1; fi
	$(EXEC_WEB) php $(CMD)

phpunit-init: ## Initialise the PHPUnit test environment
	$(EXEC_WEB) php admin/tool/phpunit/cli/init.php

phpunit: ## Run local_btcrewards PHPUnit tests
	$(EXEC_WEB) vendor/bin/phpunit --testsuite local_btcrewards_testsuite || \
		$(EXEC_WEB) vendor/bin/phpunit local/btcrewards
