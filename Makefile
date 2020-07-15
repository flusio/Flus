.DEFAULT_GOAL := help

USER = $(shell id -u):$(shell id -g)

ifdef NO_DOCKER
	PHP = php
	COMPOSER = composer
	NPM = npm
else
	PHP = ./docker/bin/php
	COMPOSER = ./docker/bin/composer
	NPM = ./docker/bin/npm
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

.PHONY: start
start: ## Start a development server (use Docker)
	@echo "Running webserver on http://localhost:8000"
	docker-compose -f docker/docker-compose.yml up

.PHONY: stop
stop: ## Stop and clean Docker server
	docker-compose -f docker/docker-compose.yml down

.PHONY: install
install: ## Install the dependencies
	$(COMPOSER) install
	$(NPM) install

.PHONY: setup
setup: .env ## Setup the application system
	$(PHP) ./cli --request /system/setup

.PHONY: update
update: setup ## Update the application

.PHONY: reset
reset: ## Reset the database
	rm data/migrations_version.txt || true
	$(PHP) ./cli --request /system/setup

.PHONY: reset-demo
reset-demo: ## Reset the database and create a demo user
	rm data/migrations_version.txt || true
	$(PHP) ./cli --request /system/setup
	$(PHP) ./cli --request /users/create -pusername=Abby -pemail=demo@flus.io -ppassword=demo

.PHONY: test
test: ## Run the test suite
	$(PHP) ./vendor/bin/phpunit \
		$(COVERAGE) --whitelist ./src --whitelist ./lib/SpiderBits \
		--bootstrap ./tests/bootstrap.php \
		--testdox \
		$(PHPUNIT_FILTER) \
		$(PHPUNIT_FILE)

.PHONY: lint
lint: ## Run the linters on the PHP and JS files
	$(PHP) ./vendor/bin/phpcs --extensions=php --standard=PSR12 ./src ./tests ./lib/SpiderBits
	$(NPM) run lint-js
	$(NPM) run lint-css

.PHONY: lint-fix
lint-fix: ## Fix the errors detected by the linters
	$(PHP) ./vendor/bin/phpcbf --extensions=php --standard=PSR12 ./src ./tests ./lib/SpiderBits
	$(NPM) run lint-js-fix
	$(NPM) run lint-css-fix

.PHONY: release
release: ## Release a new version (take a VERSION argument)
ifndef VERSION
	$(error You need to provide a "VERSION" argument)
endif
	echo $(VERSION) > VERSION.txt
	$(NPM) run build
	$(EDITOR) CHANGELOG.md
	git add .
	git commit -m "release: Publish version v$(VERSION)"
	git tag -a v$(VERSION) -m "Release version v$(VERSION)"

.PHONY: tree
tree:  ## Display the structure of the application
	tree -I 'Minz|vendor|node_modules|coverage' --dirsfirst -CA

.PHONY: help
help:
	@grep -h -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

.env:
	@cp env.sample .env
