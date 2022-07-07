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

# Constants
CORES?=$(shell (nproc  || sysctl -n hw.ncpu) 2> /dev/null)
QA_PHP_VERSION?=8.1
JAZKAL_PHP_QA_IMAGE_VERSION=1.72.2

#
# =================================================================
# Define a helper function for parallel execution
# =================================================================
#
# We want to run qa targets in parallel using make.
# For this, we define two functions that only print the full command
# output for a non-zero exit code.
#
# For successful execution only a short summary is printed.
# This behaviour can be controlled with the QUIET argument.
#
QUIET?=0

EXTERNAL_TOOL_COMMAND_OPTIONS=--interactive
ifeq ($(QUIET),false)
	EXTERNAL_TOOL_COMMAND_OPTIONS+= --tty
endif

#
# =================================================================
# Parallel execution helper
# =================================================================
#
# Usage: $(call parallel_execute_helper, "Tests" vendor/bin/codeception run unit --ansi)
#
define parallel_execute_helper
    if [ "$(QUIET)" == "0" ]; then \
        eval "$(1)"; \
    else \
        START=$$(date +%s); \
        printf "%-50s" "$(2)"; \
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

define execute_qa_tool_in_app_container
	 $(call parallel_execute_helper, $(MAYBE_EXEC_APP_IN_DOCKER) $(1), $(@))
endef

EXTERNAL_TOOL_IMAGE:=jakzal/phpqa:$(JAZKAL_PHP_QA_IMAGE_VERSION)-php$(QA_PHP_VERSION)
EXTERNAL_TOOL_COMMAND?=docker run $(EXTERNAL_TOOL_COMMAND_OPTIONS) --rm -v "$$(pwd):/project" -w /project -v $(SNICCO_QA_CACHE_DIR):$(SNICCO_QA_CACHE_DIR) $(EXTERNAL_TOOL_IMAGE)
define execute_qa_tool_in_external_container
    $(call parallel_execute_helper, $(EXTERNAL_TOOL_COMMAND) $(1), $(@))
endef

.PHONY: ecs
ecs: ## Run easy coding standards on the codebase without applying fixes.
	$(call execute_qa_tool_in_app_container, vendor/bin/ecs check --ansi $(ARGS))

.PHONY: psalm
psalm: ## Run psalm on the codebase without applying fixes.
	# We need at least one vendor directory in order to not break psalms exclude configuration.
	$(call execute_qa_tool_in_app_container, mkdir -p src/Snicco/component/asset/vendor || true && vendor/bin/psalm --threads=$(CORES) $(ARGS))

.PHONY: rector
rector: ARGS?=--dry-run
rector: ## Run rector on the codebase without applying fixes.
	$(call execute_qa_tool_in_app_container, vendor/bin/rector process $(ARGS) --ansi )

COMPOSER_FILES=composer.json $(wildcard src/Snicco/*/*/composer.json)

.PHONY: composer-normalize $(COMPOSER_FILES)
composer-normalize: $(COMPOSER_FILES)

$(COMPOSER_FILES): NORMALIZE_ARGS?=--dry-run
$(COMPOSER_FILES): VALIDATE_ARGS_ARGS?=
$(COMPOSER_FILES):
	$(eval PACKAGE_DIR := $(firstword $(subst /composer.json, ,$(@))))
	$(eval PACKAGE_NAME := $(lastword $(subst /, ,$(PACKAGE_DIR))))
	$(call execute_qa_tool_in_app_container,composer-normalize $(@) --ansi --indent-size=2 --indent-style=space $(NORMALIZE_ARGS),  composer-normalize ($(PACKAGE_NAME)))
	$(call execute_qa_tool_in_app_container, composer validate $(@) --ansi --strict $(VALIDATE_ARGS_ARGS), composer-validate ($(PACKAGE_NAME)) )

.PHONY: composer-unused
composer-unused: ## Check for unused composer packages.
	$(call execute_qa_tool_in_external_container, composer-unused $(ARGS))

.PHONY: copy-paste-detector
copy-paste-detector: EXCLUDED_VENDOR_DIRS=$(foreach dir, $(wildcard ./src/Snicco/*/*/vendor), --exclude $(dir))
copy-paste-detector: EXCLUDED_GENERATED_DIRS=$(foreach dir, $(wildcard ./src/Snicco/*/*/tests/_support/_generated), --exclude $(dir))
copy-paste-detector: ## Checks for copy-paste occurrences.
	$(call execute_qa_tool_in_external_container, phpcpd ./src $(ARGS) \
	--exclude src/skeleton \
	$(EXCLUDED_VENDOR_DIRS) \
	$(EXCLUDED_GENERATED_DIRS) \
	)

.PHONY: phploc
phploc: DIR?=src
phploc: ## Shows metrics about size and structure or the codebase
	$(call execute_qa_tool_in_external_container, phploc $(DIR) $(ARGS))

.PHONY: composer-require-checker
composer-require-checker: ## Check that all dependencies are declared in composer.json.
ifeq ($(QA_PHP_VERSION),7.4)
	$(call execute_qa_tool_in_external_container, composer-require-checker-3 --config-file=/project/composer-require-checker.json $(ARGS))
else
	$(call execute_qa_tool_in_external_container, composer-require-checker --config-file=/project/composer-require-checker.json $(ARGS))
endif

.PHONY: bc-check
bc-check: roave-backward-compatibility-check ## Check that all changes in the current working tree are backwards compatible.

.PHONY: roave-backward-compatibility-check
# We have to use a different docker image here. The other one throws a fatal error for the tool.
roave-backward-compatibility-check: EXTERNAL_TOOL_COMMAND="docker run $(EXTERNAL_TOOL_COMMAND_OPTIONS) --rm -v "$$(pwd):/app" -v $(SNICCO_QA_CACHE_DIR):$(SNICCO_QA_CACHE_DIR) nyholm/roave-bc-check:stable"
roave-backward-compatibility-check:
	if [ $(shell git tag -l) ]; then \
  		$(call execute_qa_tool_in_external_container, $(ARGS) --ansi); \
	else \
    	printf '$(YELLOW)Skipping BC check. Repo has no tags yet.\n$(NO_COLOR)'; \
	fi

.PHONY: magic-number-detector
magic-number-detector: ## Checks that the codebase does not contain magic numbers.
	$(call execute_qa_tool_in_external_container, phpmnd ./ $(ARGS) \
		--exclude-path=psalm \
		--exclude-path=src/Snicco/bundle/fortress-bundle/tests/_support/_generated \
		--exclude-path=.wp \
		--include-numeric-string \
		--non-zero-exit-on-violation \
	) # '--non-zero-exit-on-violation' might be removed in the future (https://github.com/povils/phpmnd/commit/028e0e0d1e9ed73d9468b8b724453401e9a7400c)

.PHONY: qa
qa: ## Run code quality tools on all files.
	@printf "$(GREEN)Running QA tools in parallel...$(NO_COLOR)\n"
	$(MAKE) --silent --jobs $(CORES) --keep-going --no-print-directory --output-sync qa_all

.PHONY: qa_all
qa_all: composer-normalize \
	ecs \
	rector \
    psalm \
    composer-unused \
    copy-paste-detector \
    roave-backward-compatibility-check \
    magic-number-detector \
    composer-require-checker

.PHONY: fix-cs
fix-cs: ## Apply automatic fixes for the entire codebase.
	$(MAKE) composer-normalize --silent --jobs $(CORES) --output-sync NORMALIZE_ARGS=--diff
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/rector process --ansi
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/ecs check --fix --ansi

.PHONY: clear-qa-cache
clear-qa-cache: ## Clear all caches of QA tools
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/psalm --clear-cache || true
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/psalm --clear-global-cache || true
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/ecs check --clear-cache || true
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/rector --dry-run --clear-cache || true

.PHONY: commitlint
commitlint: ## Check a commit message against our commit message rules. Usage make commitlint MSG="chore(monorepo): is this valid"
	$(if $(MSG),,$(error "Usage: make commitlint MSG=chore(monorepo): is this valid?"))
	$(MAYBE_EXEC_NODE_IN_DOCKER) echo $(MSG) | yarn commitlint

.PHONY: commitlint-from
commitlint-from: ## Checks all commit message after the provided commit sha. Usage: make commitlint-from COMMIT_SHA=4234235423123.
	$(if $(COMMIT_SHA),,$(error "Usage: make commitlint-from: COMMIT_SHA=4234235423123"))
	$(MAYBE_EXEC_NODE_IN_DOCKER) yarn commitlint --from $(COMMIT_SHA)
