.DEFAULT_GOAL := help

USER = $(shell id -u):$(shell id -g)

DOCKER_COMPOSE = docker compose -p flusio -f docker/docker-compose.yml

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

ifndef COVERAGE
	COVERAGE = --coverage-html ./coverage
endif

ifdef FILTER
	PHPUNIT_FILTER = --filter=$(FILTER)
else
	PHPUNIT_FILTER =
endif

ifdef FILE
	PHPUNIT_FILE = $(FILE)
else
	PHPUNIT_FILE = ./tests
endif

.PHONY: docker-start
docker-start: .env ## Start a development server with Docker
	@echo "Running webserver on http://localhost:8000"
	$(DOCKER_COMPOSE) up

.PHONY: docker-clean
docker-clean: ## Stop and clean Docker server
	$(DOCKER_COMPOSE) down

.PHONY: docker-build
docker-build: ## Rebuild the Docker images
	$(DOCKER_COMPOSE) build

.PHONY: install
install: ## Install the dependencies
	$(COMPOSER) install
	$(NPM) install

.PHONY: setup
setup: .env ## Setup the application system
	$(CLI) migrations setup --seed

.PHONY: rollback
rollback: ## Reverse the last migration
ifdef STEPS
	$(CLI) migrations rollback --steps=$(STEPS)
else
	$(CLI) migrations rollback
endif

.PHONY: reset
reset: ## Reset the database
ifndef FORCE
	$(error Please run the operation with FORCE=true)
endif
	$(DOCKER_COMPOSE) stop job_worker
	$(CLI) migrations reset --force --seed
	$(DOCKER_COMPOSE) start job_worker

.PHONY: icons-build
icons-build: ## Build the icons asset
	$(NPM) run build:icons

.PHONY: test
test: ## Run the test suite
	$(PHP) ./vendor/bin/phpunit \
		$(COVERAGE) --coverage-filter ./src --coverage-filter ./lib/SpiderBits \
		--bootstrap ./tests/bootstrap.php \
		--testdox \
		$(PHPUNIT_FILTER) \
		$(PHPUNIT_FILE)

.PHONY: lint
lint: ## Run the linters on the PHP and JS files
	$(PHP) ./vendor/bin/phpstan analyse --memory-limit 1G -c phpstan.neon
	$(PHP) ./vendor/bin/phpcs --extensions=php --ignore=./src/views/ --standard=PSR12 ./src ./tests ./lib/SpiderBits
	$(NPM) run lint-js
	$(NPM) run lint-css

.PHONY: lint-fix
lint-fix: ## Fix the errors detected by the linters
	$(PHP) ./vendor/bin/phpcbf --extensions=php --ignore=./src/views/ --standard=PSR12 ./src ./tests ./lib/SpiderBits
	$(NPM) run lint-js-fix
	$(NPM) run lint-css-fix

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
