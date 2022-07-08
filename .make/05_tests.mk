##@ [Tests]

PACKAGES?=$(wildcard src/Snicco/*/*)
PACKAGES_AND_SUITES:=$(foreach dir, $(PACKAGES), $(foreach suite, $(shell bash bin/package-suites.sh $(dir)), $(dir)!$(suite)))

.PHONY: test-parallel-docker tests-in-docker $(PACKAGES_AND_SUITES)
test-parallel-docker: ARGS?=--keep-going --no-print-directory --output-sync
test-parallel-docker: JOBS?=$(shell expr $(CORES) + 1 )
test-parallel-docker: _is_ci
	$(MAKE) --silent --jobs $(JOBS) $(ARGS) tests-in-docker

tests-in-docker: $(PACKAGES_AND_SUITES)

$(PACKAGES_AND_SUITES): DOCKER_UP_ARGS?=--detach --build
$(PACKAGES_AND_SUITES): SERVICE?=
$(PACKAGES_AND_SUITES):
	echo "------------------------------------------------------------------"
	$(eval PACKAGE_DIR := $(firstword $(subst !, ,$@))) # src/Snicco/plugin/snicco-fortress
	$(eval PACKAGE_NAME := $(lastword $(subst /, ,$(PACKAGE_DIR)))) # snicco-fortress
	$(eval SUITE := $(lastword $(subst !, ,$@))) # unit
	$(eval NAME := $(PACKAGE_NAME)-$(SUITE))
	$(eval DOCKER_COMPOSE_PROJECT_NAME := $(PACKAGE_NAME)-$(SUITE)-tests)
	$(call parallel_execute_helper, $(DOCKER_COMPOSE) up $(SERVICE) $(DOCKER_UP_ARGS), $(PACKAGE_NAME):$(SUITE) (docker-up))
	$(call parallel_execute_helper, $(MAYBE_EXEC_APP_IN_DOCKER) bash bin/test-package-suite.sh $(PACKAGE_DIR) $(SUITE), $(PACKAGE_NAME):$(SUITE) (tests) )
	$(call parallel_execute_helper, $(DOCKER_COMPOSE) down, $(PACKAGE_NAME):$(SUITE) (docker-down))


.PHONY: test-package-docker
test-package-docker:
	@$(if $(NAMES),,$(error "Usage: make test-package NAMES=snicco-fortress"))
	$(MAKE) test-parallel ARGS=--keep-going --no-print-directory PACKAGES="$(foreach name,$(NAMES),$(wildcard src/Snicco/*/$(name)))"

.PHONY: test-affected
test-affected:
	$(MAYBE_EXEC_APP_IN_DOCKER) bash bin/test-affected-packages.sh

.PHONY: test
test:
	$(MAYBE_EXEC_APP_IN_DOCKER) bash bin/tests-local.sh

.PHONY: test-coverage
test-coverage: DOCKER_EXEC_ARGS+=-e COVERAGE=1
test-coverage:
	$(MAKE) xdebug-on SERVICE=$(DOCKER_SERVICE_APP_NAME) --no-print-directory
	$(MAYBE_EXEC_APP_IN_DOCKER) bash bin/tests-local.sh
	$(MAKE) xdebug-off SERVICE=$(DOCKER_SERVICE_APP_NAME) --no-print-directory