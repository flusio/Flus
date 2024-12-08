.DEFAULT_GOAL := help

USER = $(shell id -u):$(shell id -g)

DOCKER_COMPOSE = docker compose -f docker/development/docker-compose.yml

ifdef NO_DOCKER
	PHP = php
	COMPOSER = composer
	NPM = npm
	CLI = php cli
else
	PHP = ./docker/bin/php
	COMPOSER = ./docker/bin/composer
	NPM = ./docker/bin/npm
	CLI = ./docker/bin/cli
endif

.PHONY: docker-start
docker-start: PORT ?= 8000
docker-start: .env ## Start a development server (can take a PORT argument)
	@echo "Running webserver on http://localhost:$(PORT)"
	$(DOCKER_COMPOSE) up

.PHONY: docker-build
docker-build: ## Rebuild the Docker images
	$(DOCKER_COMPOSE) build --pull

.PHONY: docker-pull
docker-pull: ## Pull the Docker images from the Docker Hub
	$(DOCKER_COMPOSE) pull --ignore-buildable

.PHONY: docker-clean
docker-clean: ## Clean the Docker stuff
	$(DOCKER_COMPOSE) down -v

.PHONY: install
install: INSTALLER ?= all
install: ## Install the dependencies (can take an INSTALLER argument)
ifeq ($(INSTALLER), $(filter $(INSTALLER), all composer))
	$(COMPOSER) install
endif
ifeq ($(INSTALLER), $(filter $(INSTALLER), all npm))
	$(NPM) install
endif

.PHONY: db-setup
db-setup: .env ## Setup and migrate the application system
	$(CLI) migrations setup --seed

.PHONY: db-rollback
db-rollback: ## Reverse the last migration (can take a STEPS argument)
ifdef STEPS
	$(CLI) migrations rollback --steps=$(STEPS)
else
	$(CLI) migrations rollback
endif

.PHONY: db-reset
db-reset: ## Reset the database (take a FORCE argument)
ifndef FORCE
	$(error Please run the operation with FORCE=true)
endif
ifndef NO_DOCKER
	$(DOCKER_COMPOSE) stop job_worker
endif
	$(CLI) migrations reset --force --seed
ifndef NO_DOCKER
	$(DOCKER_COMPOSE) start job_worker
endif

.PHONY: icons
icons: ## Build the icons asset
	$(NPM) run build:icons

.PHONY: test
test: FILE ?= ./tests
ifdef FILTER
test: override FILTER := --filter=$(FILTER)
endif
test: COVERAGE ?= --coverage-html ./coverage
test: ## Run the test suite (can take FILE, FILTER and COVERAGE arguments)
	$(PHP) ./vendor/bin/phpunit \
		-c .phpunit.xml \
		$(COVERAGE) \
		$(FILTER) \
		$(FILE)

.PHONY: lint
lint: LINTER ?= all
lint: ## Execute the linters (can take a LINTER argument)
ifeq ($(LINTER), $(filter $(LINTER), all phpstan))
	$(PHP) vendor/bin/phpstan analyse --memory-limit 1G -c .phpstan.neon
endif
ifeq ($(LINTER), $(filter $(LINTER), all rector))
	$(PHP) vendor/bin/rector process --dry-run --config .rector.php
endif
ifeq ($(LINTER), $(filter $(LINTER), all phpcs))
	$(PHP) vendor/bin/phpcs
endif
ifeq ($(LINTER), $(filter $(LINTER), all js))
	$(NPM) run lint-js
endif
ifeq ($(LINTER), $(filter $(LINTER), all css))
	$(NPM) run lint-css
endif

.PHONY: lint-fix
lint-fix: LINTER ?= all
lint-fix: ## Fix the errors detected by the linters (can take a LINTER argument)
ifeq ($(LINTER), $(filter $(LINTER), all rector))
	$(PHP) vendor/bin/rector process --config .rector.php
endif
ifeq ($(LINTER), $(filter $(LINTER), all phpcs))
	$(PHP) vendor/bin/phpcbf
endif
ifeq ($(LINTER), $(filter $(LINTER), all js))
	$(NPM) run lint-js-fix
endif
ifeq ($(LINTER), $(filter $(LINTER), all css))
	$(NPM) run lint-css-fix
endif

.PHONY: release
release: ## Release a new version (take a VERSION argument)
ifndef VERSION
	$(error You need to provide a "VERSION" argument)
endif
	echo $(VERSION) > VERSION.txt
	rm -rf public/assets/*
	$(NPM) run build
	$(EDITOR) CHANGELOG.md
	git add .
	git commit -m "release: Publish version v$(VERSION)"
	git tag -a v$(VERSION) -m "Release version v$(VERSION)"

.PHONY: tree
tree:  ## Display the structure of the application
	tree -I 'Minz|vendor|node_modules|coverage|cache|dev_assets|media' --dirsfirst -CA

.PHONY: help
help:
	@grep -h -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

.env:
	@cp env.sample .env
