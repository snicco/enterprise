##@ [Quality Assurance]

.PHONY: commitlint \
		commitlint-from \
		unit-tests \
		wpunit-tests \
		usecase-tests \
		integration-tests \
		cli-tests \
		browser-tests \
		tests \
		lint \
		lint-fix \
		psalm \
		codecept \
		composer-unused \
		copy-paste-detector \
		phploc \
		parallel-lint \
		composer-require-checker

#
# =================================================================
# Cores
# =================================================================
#
# Determine the number of cores to use for parallel execution.
# A value can also be set in the configuration and overwrites
# this one.
#
CORES?=$(shell (nproc  || sysctl -n hw.ncpu) 2> /dev/null)
QA_PHP_VERSION?=7.4

#
# =================================================================
# CLI Tool configurations
# =================================================================
#
PSALM_ARGS?=
ECS_ARGS?=
COMPOSER_UNUSED_ARGS?=

NO_PROGRESS?=false
ifeq ($(NO_PROGRESS),true)
    ECS_ARGS+= --no-ansi --no-progress-bar
    PSALM_ARGS+= --no-progress
    COMPOSER_UNUSED_ARGS+= --no-progress --ansi
else
endif

define execute
    if [ "$(NO_PROGRESS)" = "false" ]; then \
        eval "$(MAYBE_EXEC_APP_IN_DOCKER) $(1) $(2)"; \
    else \
        START=$$(date +%s); \
        printf "%-35s" "$@"; \
        if OUTPUT=$$(eval "$(MAYBE_EXEC_APP_IN_DOCKER) $(1) $(2)" 2>&1); then \
            printf " $(GREEN)%-6s$(NO_COLOR)" "Ok"; \
            END=$$(date +%s); \
            RUNTIME=$$((END-START)) ;\
            printf " took $(YELLOW)$${RUNTIME}s$(NO_COLOR)\n"; \
        else \
            printf " $(RED)%-6s$(NO_COLOR)" "fail"; \
            END=$$(date +%s); \
            RUNTIME=$$((END-START)) ;\
            printf " took $(YELLOW)$${RUNTIME}s$(NO_COLOR)\n"; \
            echo "$$OUTPUT"; \
            printf "\n"; \
            exit 1; \
        fi; \
    fi
endef

define execute_in_external_docker_container
    if [ "$(NO_PROGRESS)" = "false" ]; then \
        eval "$(1)"; \
    else \
        START=$$(date +%s); \
        printf "%-35s" "$@"; \
        if OUTPUT=$$(eval "$(1)" 2>&1); then \
            printf " $(GREEN)%-6s$(NO_COLOR)" "Ok"; \
            END=$$(date +%s); \
            RUNTIME=$$((END-START)) ;\
            printf " took $(YELLOW)$${RUNTIME}s$(NO_COLOR)\n"; \
        else \
            printf " $(RED)%-6s$(NO_COLOR)" "fail"; \
            END=$$(date +%s); \
            RUNTIME=$$((END-START)) ;\
            printf " took $(YELLOW)$${RUNTIME}s$(NO_COLOR)\n"; \
            echo "$$OUTPUT"; \
            printf "\n"; \
            exit 1; \
        fi; \
    fi
endef

# @todo Add rector once compatible with codeception
ecs: ## Lint the codebase without applying fixes.
	@$(call execute, vendor/bin/ecs check, $(ECS_ARGS))

psalm: ## Run psalm on the entire codebase.
	@$(call execute, vendor/bin/psalm , $(PSALM_ARGS))

composer-unused: ## Check for unused composer packages
	@$(call execute, composer-unused , $(COMPOSER_UNUSED_ARGS))

copy-paste-detector: ## Checks for copy-paste occurrences
	@$(call execute, phpcpd , ./src --exclude ./src/Snicco/bundle/fortress-bundle/tests/_support/_generated --exclude ./src/Snicco/skeleton/)

phploc: DIR?=src
phploc: ## Shows metrics about size and structure or the codebase
	$(MAYBE_EXEC_APP_IN_DOCKER) phploc $(DIR) $(ARGS)

parallel-lint: ## Checks the syntax of all files.
	@$(call execute_in_external_docker_container,  docker run -i --rm -v "$$(pwd):/project:ro" -w /project jakzal/phpqa:php$(QA_PHP_VERSION)-alpine parallel-lint . \
                                                  	--exclude .git \
                                                  	--exclude vendor \
                                                  	--exclude .wp \
                                                  	--exclude .docker \
                                                  	--exclude .github \
                                                  	--exclude .psalm \
                                                  	)

composer-require-checker: ## Check that all dependencies are declared in composer.json.
ifeq ($(QA_PHP_VERSION),7.4)
	@$(call execute_in_external_docker_container,  docker run --init -i --rm -v "$$(pwd):/project:ro" -w /project jakzal/phpqa:php$(QA_PHP_VERSION)-alpine composer-require-checker-3)
else
	@$(call execute_in_external_docker_container,  docker run --init -i --rm -v "$$(pwd):/project:ro" -w /project jakzal/phpqa:php$(QA_PHP_VERSION)-alpine composer-require-checker)
endif

new:
	@$(call execute_in_external_docker_container,  docker run --init -i --rm -v "$$(pwd):/project:ro" -w /project jakzal/phpqa:php$(QA_PHP_VERSION)-alpine rector process --dry-run)

.PHONY: qa
qa: ## Run code quality tools on all files.
	@"$(MAKE)" --jobs $(CORES) --keep-going --no-print-directory --output-sync qa_all NO_PROGRESS=true

.PHONY: _qa_all
qa_all: ecs \
    psalm \
    composer-unused \
    copy-paste-detector \
    parallel-lint \
#    composer-requirechecker \

codecept: ## Run codeception for a set of files. Usage: make codecept FILES=src/Snicco/component/asset/tests/unit/AssetFactoryTest.
	@$(if $(FILES),,$(error "Usage: make codecept FILES=src/Snicco/component/asset/tests/unit/AssetFactoryTest"))
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/codecept run $(FILES) $(ARGS)

tests: unit-tests wpunit-tests usecase-tests integration-tests cli-tests browser-tests ## Run all tests for all packages.
	@echo "All tests done."

unit-tests: ## Run all unit suites for all packages.
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/codecept run unit,*::unit $(ARGS)

wpunit-tests: ## Run all wp-unit suites for all packages.
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/codecept run wpunit,*::wpunit $(ARGS)

usecase-tests: ## Run all usecase test suites for all packages.
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/codecept run usecase,*::usecase $(ARGS)

integration-tests: ## Run all integration suites for all packages.
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/codecept run integration,*::integration $(ARGS)

browser-tests: ## Run all browser test suites for all packages.
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/codecept run browser,*::browser $(ARGS)

cli-tests: ## Run all cli test suites for all packages.
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/codecept run cli,*::cli $(ARGS)

# @todo Add rector once compatible with codeception
fix: ## Fix linting errors.
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/ecs --fix $(ARGS)

clear-qa-cache: ## Clear all caches of QA tools
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/psalm --clear-cache
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/psalm --clear-global-cache
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/ecs check --clear-cache

commitlint: ## Check a commit message against our commit message rules. Usage make commitlint MSG="chore(monorepo): is this valid"
	@$(if $(MSG),,$(error "Usage: make commitlint MSG=chore(monorepo): is this valid?"))
	$(MAYBE_EXEC_NODE_IN_DOCKER) echo ${MSG} | npx commitlint

commitlint-from: ## Checks all commit message after the provided commit sha. Usage: make commitlint-from COMMIT_SHA=4234235423123.
	@$(if $(COMMIT_SHA),,$(error "Usage: make commitlint-from: COMMIT_SHA=4234235423123"))
	$(MAYBE_EXEC_NODE_IN_DOCKER) npx commitlint --from ${COMMIT_SHA}