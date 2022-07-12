##@ [Tests]

#
# =================================================================
# Testing strategy
# =================================================================
#
# We separate test commands into two categories:
# 1) Commands that are fast, where the main purpose is fast developer feedback
# 2) Commands that are slower but verify more.
#
# Category 1) uses the merged composer autoloader in the monorepo root which means
# that we dont need to install dependencies for each package individually but
# it also leaves open possibilities for errors where a package uses a dependency
# that it does not declare in its composer.json
#
# Category 2) will always install dependencies for the tested package and run
# tests from that packages directory.
#

#
# =================================================================
# Test affected packages
# =================================================================
#
# This will test all packages that have been affected by the changes since the
# since the LAST COMMIT.
#
.PHONY: test-affected
test-affected: ISOLATED?=0
test-affected: FAST_ONLY?=0
test-affected: DOCKER_EXEC_ARGS+= --env ISOLATED=$(ISOLATED) --env FAST_ONLY=$(FAST_ONLY)
test-affected: _is_local ## Test all packages affected since the last commit.
	$(MAYBE_EXEC_APP_IN_DOCKER) bash ./bin/test/affected-packages.sh

.PHONY: test-affected-fast
test-affected-fast: _is_local ## Run unit and use-case suites for all packages affected since the last commit.
	FAST_ONLY=1 $(MAKE) test-affected --no-print-directory

#
# =================================================================
# Test all packages sequentially
# =================================================================
#
# This command will run all tests grouped by their suite
# for the entire monorepo. It will still use the monorepo autoloader.
#
.PHONY: test-sequential
test-sequential: ## Run all suites of all packages using the root autoloader.
	$(MAYBE_EXEC_APP_IN_DOCKER) bash bin/test/sequential.sh

#
# =================================================================
# Collect test coverage
# =================================================================
#
# Collect test coverage for all packages by running all suites
# sequentially.
#
.PHONY: test-coverage
test-coverage: DOCKER_EXEC_ARGS+=--env COVERAGE=1
test-coverage:
	$(MAKE) xdebug-on SERVICE=$(DOCKER_SERVICE_APP_NAME) --no-print-directory
	$(MAYBE_EXEC_APP_IN_DOCKER) bash bin/test/sequential.sh
	$(MAKE) xdebug-off SERVICE=$(DOCKER_SERVICE_APP_NAME) --no-print-directory

#
# =================================================================
# Test a single package
# =================================================================
#
# Test suites from a single package. Either in isolation or using
# the root autoloader.
#
# Usage: make test-package PACKAGE=asset SUITES=unit
# Usage: ISOLATED=1 make test-package PACKAGE=snicco-fortress SUITES=unit
#
.PHONY: test-package
test-package: ISOLATED?=0
test-package: SUITES=
test-package: DOCKER_EXEC_ARGS+= --env ISOLATED=$(ISOLATED)
test-package: ## Tests suites from a single package.
	$(if $(PACKAGE),,$(error "Usage: make test-package PACKAGE=snicco-fortress"))
	$(MAYBE_EXEC_APP_IN_DOCKER) bash bin/test/package.sh $(wildcard src/Snicco/*/$(PACKAGE)) $(SUITES)

#
# =================================================================
# Test packages in parallel
# =================================================================
#
# Tests all packages in parallel, each in a separate docker network.
#
PACKAGES?=$(wildcard src/Snicco/*/*)

.PHONY: $(PACKAGES) test-packages-parallel _test-packages-parallel
test-packages-parallel: _is_ci ## Tests all packages in parallel in separate docker networks.
	$(MAKE) --silent --jobs $(JOBS) --keep-going --no-print-directory --output-sync _test-packages-parallel

_test-packages-parallel: $(PACKAGES)

$(PACKAGES): SERVICE?=
$(PACKAGES): DOCKER_UP_ARGS?=--detach --build
$(PACKAGES): DOCKER_EXEC_ARGS+= --env ISOLATED=1
$(PACKAGES):
	$(eval PACKAGE_NAME := $(lastword $(subst /, ,$(@)))) # snicco-fortress
	$(eval DOCKER_COMPOSE_PROJECT_NAME := $(PACKAGE_NAME)-tests)
	$(call parallel_execute_helper, $(DOCKER_COMPOSE) up $(SERVICE) $(DOCKER_UP_ARGS), $(PACKAGE_NAME): (docker-up))
	$(call parallel_execute_helper, $(MAYBE_EXEC_APP_IN_DOCKER) bash bin/test/package.sh $(@), $(PACKAGE_NAME): (tests) )
	$(call parallel_execute_helper, $(DOCKER_COMPOSE) down, $(PACKAGE_NAME): (docker-down))


#PACKAGES_AND_SUITES?=$(foreach dir, $(PACKAGES), $(foreach suite, $(shell bash bin/test/get-package-codeception-suites.sh $(dir)), $(dir)!$(suite)))
#
#.PHONY: test-parallel-docker tests-in-docker $(PACKAGES_AND_SUITES)
#test-parallel-docker: ARGS?=--keep-going --no-print-directory --output-sync
#test-parallel-docker: JOBS?=$(shell expr $(CORES) + 1 )
#test-parallel-docker: _is_ci
#	$(MAKE) --silent --jobs $(JOBS) $(ARGS) tests-in-docker
#
#tests-in-docker: $(PACKAGES_AND_SUITES)
#
#$(PACKAGES_AND_SUITES): DOCKER_UP_ARGS?=--detach --build
#$(PACKAGES_AND_SUITES): SERVICE?=
#$(PACKAGES_AND_SUITES):
#	echo "------------------------------------------------------------------"
#	$(eval PACKAGE_DIR := $(firstword $(subst !, ,$@))) # src/Snicco/plugin/snicco-fortress
#	$(eval PACKAGE_NAME := $(lastword $(subst /, ,$(PACKAGE_DIR)))) # snicco-fortress
#	$(eval SUITE := $(lastword $(subst !, ,$@))) # unit
#	$(eval NAME := $(PACKAGE_NAME)-$(SUITE))
#	$(eval DOCKER_COMPOSE_PROJECT_NAME := $(PACKAGE_NAME)-$(SUITE)-tests)
#	$(call parallel_execute_helper, $(DOCKER_COMPOSE) up $(SERVICE) $(DOCKER_UP_ARGS), $(PACKAGE_NAME):$(SUITE) (docker-up))
#	$(call parallel_execute_helper, $(MAYBE_EXEC_APP_IN_DOCKER) bash bin/test-package-suite.sh $(PACKAGE_DIR) $(SUITE), $(PACKAGE_NAME):$(SUITE) (tests) )
#	$(call parallel_execute_helper, $(DOCKER_COMPOSE) down, $(PACKAGE_NAME):$(SUITE) (docker-down))
#
#.PHONY: test-package-docker
#test-package-docker:
#	@$(if $(NAMES),,$(error "Usage: make test-package NAMES=snicco-fortress"))
#	$(MAKE) test-parallel ARGS=--keep-going --no-print-directory PACKAGES="$(foreach name,$(NAMES),$(wildcard src/Snicco/*/$(name)))"

