##@ [Quality Assurance]

#
# =================================================================
# Quality assurance tools:
# =================================================================
#
# We split the usage of our QA tools into two categories:
#
# 1) Critical QA tools that need tight version constraints and configuration
# 2) Other tools that are "run and forget"
#
# Tools from category 1) are including as composer dependencies
# and run in the "app" container.
#
# Tools from category 1) are run using the docker image of
# https://github.com/jakzal/phpqa
#
# 1) rector, ecs, codeception, psalm
# 2) anything else in this file
#

CORES?=$(shell (nproc  || sysctl -n hw.ncpu) 2> /dev/null)
QA_PHP_VERSION?=8.1
QA_DOCKER_RUN_OPTIONS=-it

#
# =================================================================
# CLI Tool configurations
# =================================================================
#
PSALM_ARGS?=
ECS_ARGS?=
COMPOSER_UNUSED_ARGS?=

QUIET?=false
#ifeq ($(QUIET),true)
#    ECS_ARGS+= --no-ansi --no-progress-bar
#    PSALM_ARGS+= --no-progress
#    COMPOSER_UNUSED_ARGS+= --no-progress --ansi
#endif

define execute
    if [ "$(QUIET)" = "false" ]; then \
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

EXTERNAL_TOOL_COMMAND="docker run -it --rm -v "$$(pwd):/project:ro" -w /project jakzal/phpqa:$(JAZKAL_PHP_QA_IMAGE_VERSION)-php$(QA_PHP_VERSION)-alpine"

define run_external_tool
    if [ "$(QUIET)" = "false" ]; then \
        eval "$(EXTERNAL_TOOL_COMMAND) $(1)"; \
    else \
        START=$$(date +%s); \
        printf "%-35s" "$@"; \
        if OUTPUT=$$(eval "$(EXTERNAL_TOOL_COMMAND) $(1)" 2>&1); then \
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

composer-unused: ## Check for unused composer packages.
	@$(call run_external_tool, composer-unused $(ARGS))

copy-paste-detector: ## Checks for copy-paste occurrences.
	@$(call run_external_tool, phpcpd ./src $(ARGS) --exclude ./src/Snicco/bundle/fortress-bundle/tests/_support/_generated --exclude ./src/Snicco/skeleton/ )

parallel-lint: ## Checks the syntax of all files.
	@$(call run_external_tool, parallel-lint . $(ARGS) \
								--exclude .git \
                              	--exclude vendor \
                              	--exclude wp \
                              	--exclude docker \
                              	--exclude github \
                              	--exclude psalm \
                              	)

phploc: DIR?=src
phploc: ## Shows metrics about size and structure or the codebase
	@$(call run_external_tool, phploc $(DIR) $(ARGS))

composer-require-checker: ## Check that all dependencies are declared in composer.json.
ifeq ($(QA_PHP_VERSION),7.4)
	@$(call run_external_tool, composer-require-checker-3 $(ARGS))
else
	@$(call run_external_tool, composer-require-checker $(ARGS))
endif

bc-check: roave-backward-compatibility-check ## Check that all changes in the current working tree are backwards compatible.
roave-backward-compatibility-check:
	@$(call run_external_tool, roave-backward-compatibility-check $(ARGS))

numbers:
	@$(call execute_in_external_docker_container,  docker run --init -it --rm -v "$$(pwd):/project:ro" -w /project \
		jakzal/phpqa:$(JAZKAL_PHP_QA_IMAGE_VERSION)-php$(QA_PHP_VERSION)-alpine /bin/sh \
	)

.PHONY: qa
qa: ## Run code quality tools on all files.
	@"$(MAKE)" --jobs $(CORES) --keep-going --no-print-directory --output-sync qa_all NO_PROGRESS=true

.PHONY: _qa_all
qa_all: ecs \
    psalm \
    composer-unused \
    copy-paste-detector \
    parallel-lint \
    roave-backward-compatibility-check \
    composer-require-checker

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