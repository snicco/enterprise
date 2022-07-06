##@ [Tests]

.PHONY: test-affected
test-affected:
	$(MAYBE_EXEC_APP_IN_DOCKER) bash bin/test-affected-packages.sh

PACKAGES:=$(wildcard src/Snicco/*/*)

.PHONY: test-all
test-all:
	$(MAKE) --silent --jobs $(CORES) --keep-going --no-print-directory --output-sync _test-parallel

.PHONY: _test-parallel $(PACKAGES)
_test-parallel: $(PACKAGES)
$(PACKAGES):
	$(eval DOCKER_COMPOSE_PROJECT_NAME := $(lastword $(subst /, ,$@)))
	$(MAKE) docker-up DOCKER_COMPOSE_PROJECT_NAME=$(DOCKER_COMPOSE_PROJECT_NAME) MODE=--detach
	$(MAKE) test-package PACKAGE=$@ DOCKER_COMPOSE_PROJECT_NAME=$(DOCKER_COMPOSE_PROJECT_NAME)
	$(MAKE) docker-down DOCKER_COMPOSE_PROJECT_NAME=$(DOCKER_COMPOSE_PROJECT_NAME)

.PHONY: test-package
test-package:
	@$(if $(PACKAGE),,$(error "Usage: make test-package PACKAGE=src/Snicco/plugin/snicco-fortress"))
	$(MAYBE_EXEC_APP_IN_DOCKER) bin/test-package.sh $(PACKAGE)

.PHONY: test-package-suite
test-package-suite:
	@$(if $(PACKAGE),,$(error "Usage: make test-package-suite PACKAGE=src/Snicco/plugin/snicco-fortress SUITE=unit"))
	@$(if $(SUITE),,$(error "Usage: make test-package-suite PACKAGE=src/Snicco/plugin/snicco-fortress SUITE=unit"))
	$(MAYBE_EXEC_APP_IN_DOCKER) bash bin/test-package-suite.sh $(PACKAGE) $(SUITE)

FORTRESS_SUITES=$(shell sh bin/package-suites.sh src/Snicco/plugin/snicco-fortress)
FORTRESS_SUITES=unit wpunit integration usecase browser

.PHONY: fortress $(FORTRESS_SUITES)
fortress: QUIET=true
fortress: $(FORTRESS_SUITES)

$(FORTRESS_SUITES): DOCKER_COMPOSE_PROJECT_NAME=snicco-fortress-$@-tests
$(FORTRESS_SUITES): _validate-docker-env
	$(DOCKER_COMPOSE) up --detach > /dev/null 2>&1
	$(call parallel_execute_helper, "$(MAYBE_EXEC_APP_IN_DOCKER) bash bin/test-package-suite.sh", "src/Snicco/plugin/snicco-fortress $(@)")
	$(DOCKER_COMPOSE) down > /dev/null 2>&1

.PHONY:codecept
codecept: ## Run codeception for a set of files. Usage: make codecept FILES=src/Snicco/component/asset/tests/unit/AssetFactoryTest.
	@$(if $(FILES),,$(error "Usage: make codecept FILES=src/Snicco/component/asset/tests/unit/AssetFactoryTest"))
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/codecept run $(FILES) $(ARGS)

.PHONY: tests
tests: unit-tests wpunit-tests usecase-tests integration-tests cli-tests browser-tests ## Run all tests for all packages.
	@echo "All tests done."

.PHONY: unit-tests
unit-tests: ## Run all unit suites for all packages.
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/codecept run unit,*::unit $(ARGS)

.PHONY: wpunit-tests
wpunit-tests: ## Run all wp-unit suites for all packages.
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/codecept run wpunit,*::wpunit $(ARGS)

.PHONY: usecase-tests
usecase-tests: ## Run all usecase test suites for all packages.
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/codecept run usecase,*::usecase $(ARGS)

.PHONY: integration-tests
integration-tests: ## Run all integration suites for all packages.
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/codecept run integration,*::integration $(ARGS)

.PHONY: browser-tests
browser-tests: ## Run all browser test suites for all packages.
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/codecept run browser,*::browser $(ARGS)

.PHONY: cli-tests
cli-tests: ## Run all cli test suites for all packages.
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/codecept run cli,*::cli $(ARGS)

test-plugin:
	@$(MAYBE_EXEC_APP_IN_DOCKER) sh bin/test-plugin.sh src/Snicco/plugin/snicco-fortress
