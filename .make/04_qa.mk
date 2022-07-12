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
# Tools from category 1) are included as composer dependencies
# and run in the "app" docker service.
#
# Tools from category 1) are run using the docker image of
# https://github.com/jakzal/phpqa
#
# 1) rector, ecs, codeception, psalm, composer-normalize (because its not in the jakzal/phpqa image)
# 2) anything else in this file
#

#
# =================================================================
# Run all QA tools
# =================================================================
#
# This target runs all QA tools in parallel.
# Usage: make qa
# Minimal output: make qa QUIET=1
#
.PHONY: qa
qa: ## Run code quality tools on all files.
	@printf "$(GREEN)Running QA tools in parallel...$(NO_COLOR)\n"
	DOCKER_EXEC_ARGS=--no-TTY $(MAKE) --silent --jobs $(JOBS) --keep-going --no-print-directory --output-sync qa_all

.PHONY: qa_all
qa_all: psalm \
	ecs \
	rector \
    composer-unused \
    copy-paste-detector \
    backward-compatibility-check \
    magic-number-detector \
    composer-require-checker \
    composer-validate \
    composer-normalize

#
# =================================================================
# Fix all QA issues
# =================================================================
#
# Fixes all possible QA issues
#
.PHONY: fix
fix: _is_local ## Apply automatic fixes for the entire codebase.
	$(MAKE) composer-normalize --silent --jobs $(CORES) --output-sync ARGS=--diff
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/rector process --ansi
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/ecs check --fix --ansi

#
# =================================================================
# Define helper functions
# =================================================================
#
# We define two wrappers around our "parallel_execution_helper"
# function.
# 
EXTERNAL_TOOL_COMMAND_OPTIONS=--interactive
ifeq ($(QUIET),false)
	EXTERNAL_TOOL_COMMAND_OPTIONS+= --tty # Avoid the "is not a TTY" error if run in parallel
endif

define execute_qa_tool_in_app_container
	 $(call parallel_execute_helper, $(MAYBE_EXEC_APP_IN_DOCKER) $(1), $(@))
endef

EXTERNAL_TOOL_COMMAND?=docker run $(EXTERNAL_TOOL_COMMAND_OPTIONS) --rm -v "$$(pwd):/project:ro" -w /project jakzal/phpqa:1.72.2-php8.1
define execute_qa_tool_in_external_container
    $(call parallel_execute_helper, $(EXTERNAL_TOOL_COMMAND) $(1), $(@))
endef

#
# =================================================================
# Psalm
# =================================================================
#
# Psalm is used for static analysis of the PHP codebase
#
# @see https://psalm.dev/
#
.PHONY: psalm
psalm: ## Run psalm on the codebase without applying fixes.
	# We need at least one vendor directory in order to not break psalms exclude configuration.
	$(call execute_qa_tool_in_app_container, mkdir -p src/Snicco/component/asset/vendor src/Snicco/component/asset/tests/_support/_generated)
	$(call execute_qa_tool_in_app_container, vendor/bin/psalm --threads=$(CORES) $(ARGS))

#
# =================================================================
# Easy-coding-standards
# =================================================================
#
# Easy-coding-standards is used to maintain coding styles of
# the PHP codebase.
#
# @see https://github.com/symplify/easy-coding-standard
#
.PHONY: ecs
ecs: ## Run ECS on the codebase without applying fixes.
	$(call execute_qa_tool_in_app_container, vendor/bin/ecs check --ansi $(ARGS))

#
# =================================================================
# Rector
# =================================================================
#
# Rector is used to maintain code quality and for automatic
# refactoring of the PHP codebase.
#
# @see https://github.com/rectorphp/rector
#
.PHONY: rector
rector: ARGS?=--dry-run
rector: ## Run rector on the codebase without applying fixes.
	$(call execute_qa_tool_in_app_container, vendor/bin/rector process $(ARGS) --ansi )

#
# =================================================================
# Composer-unused
# =================================================================
#
# Composer-unused is a tool that detects if the a composer.json
# file requires any packages that are not used anywhere
# in the codebase. We run this on the monorepo composer.json
# since its the sum of all package composer.json files.
#
# @see https://github.com/composer-unused/composer-unused
#
.PHONY: composer-unused
composer-unused: ## Check for unused composer packages.
	$(call execute_qa_tool_in_external_container, composer-unused $(ARGS))

#
# =================================================================
# Composer require-checker
# =================================================================
#
# The opposite of composer-unused. Checks that all dependencies
# that are used in the PHP codebase are also declared in the
# composer.json file. This prevents using transitive
# dependencies of dependencies that might be removed any time.
# We currently run this on the root composer.json since it
# aggregates all composer.json files of packages.
#
# @see https://github.com/maglnet/ComposerRequireChecker
#
.PHONY: composer-require-checker
composer-require-checker: ## Check that all dependencies are declared in composer.json.
	$(call execute_qa_tool_in_external_container, composer-require-checker --config-file=/project/composer-require-checker.json $(ARGS))

#
# =================================================================
# Copy-Paste detector
# =================================================================
#
# Detects duplicate code in the PHP codebase
#
# @see https://github.com/sebastianbergmann/phpcpd
#
.PHONY: copy-paste-detector
copy-paste-detector: EXCLUDED_VENDOR_DIRS=$(foreach dir, $(wildcard ./src/Snicco/*/*/vendor), --exclude $(dir))
copy-paste-detector: EXCLUDED_GENERATED_DIRS=$(foreach dir, $(wildcard ./src/Snicco/*/*/tests/_support/_generated), --exclude $(dir))
copy-paste-detector: ## Checks for copy-paste occurrences.
	$(call execute_qa_tool_in_external_container, phpcpd ./src $(ARGS) \
	--exclude src/skeleton \
	$(EXCLUDED_VENDOR_DIRS) \
	$(EXCLUDED_GENERATED_DIRS) \
	)

#
# =================================================================
# Magic number detector
# =================================================================
#
# Detects magic numbers and magic strings in the PHP codebase.
#
# @see https://github.com/povils/phpmnd
#
.PHONY: magic-number-detector
magic-number-detector: ## Checks that the codebase does not contain magic numbers.
	$(call execute_qa_tool_in_external_container, phpmnd ./ $(ARGS) \
		--exclude-path=psalm \
		--exclude-path=src/Snicco/bundle/fortress-bundle/tests/_support/_generated \
		--exclude-path=.wp \
		--include-numeric-string \
		--non-zero-exit-on-violation \
	) # '--non-zero-exit-on-violation' might be removed in the future (https://github.com/povils/phpmnd/commit/028e0e0d1e9ed73d9468b8b724453401e9a7400c)

#
# =================================================================
# Backwards compatibility check
# =================================================================
#
# Checks that the current HEAD is not a BC break compared to the
# last semver minor version.
#
# @see https://github.com/Roave/BackwardCompatibilityCheck
#
.PHONY: backward-compatibility-check
# We have to use a different docker image here. The other one throws a fatal error for the tool.
backward-compatibility-check: EXTERNAL_TOOL_COMMAND="docker run $(EXTERNAL_TOOL_COMMAND_OPTIONS) --rm -v "$$(pwd):/app:ro" nyholm/roave-bc-check:stable"
backward-compatibility-check: ## Check that the current HEAD is not a BC break.
	if [ $(shell git tag -l) ]; then \
  		$(call execute_qa_tool_in_external_container, $(ARGS) --ansi); \
	else \
    	printf '$(YELLOW)Skipping BC check. Repo has no tags yet.\n$(NO_COLOR)'; \
	fi

#
# =================================================================
# Composer validate
# =================================================================
#
# Validates all composer.json files using "composer validate"
#
COMPOSER_FILES=composer.json $(wildcard src/Snicco/*/*/composer.json)
# We dont use the exact filenames on purpose because we want
# this to be a PHONY target that runs everytime.
COMPOSER_VALIDATE_TARGETS=$(foreach file, $(COMPOSER_FILES), $(file)-validate)
.PHONY: composer-validate $(COMPOSER_VALIDATE_TARGETS)
composer-validate: $(COMPOSER_VALIDATE_TARGETS) ## Validates all composer.json files
$(COMPOSER_VALIDATE_TARGETS):
	$(eval FILE := $(firstword $(subst -validate,,$(@))))
	$(eval PACKAGE_DIR := $(firstword $(subst /composer.json, ,$(@))))
	$(eval PACKAGE_NAME := $(lastword $(subst /, ,$(PACKAGE_DIR))))
	$(call execute_qa_tool_in_app_container, composer validate $(FILE) --ansi --strict $(ARGS))

#
# =================================================================
# Composer normalize
# =================================================================
#
# Normalizes all composer.json files using:
#
# @see https://github.com/ergebnis/composer-normalize
#
COMPOSER_NORMALIZE_TARGETS=$(foreach file, $(COMPOSER_FILES), $(file)-normalize)
.PHONY: composer-normalize $(COMPOSER_NORMALIZE_TARGETS)
composer-normalize: $(COMPOSER_NORMALIZE_TARGETS) ## Normalizes all composer.json files
composer-normalize: ARGS?=--dry-run
$(COMPOSER_NORMALIZE_TARGETS):
	$(eval FILE := $(firstword $(subst -normalize,,$(@))))
	$(call execute_qa_tool_in_app_container,composer-normalize $(FILE) --ansi --indent-size=2 --indent-style=space $(ARGS))

#
# =================================================================
# PHP Lines of code
# =================================================================
#
# Displays metrics about the PHP codebase.
#
# Usage: make phploc DIR=src/Snicco/plugin/snicco-fortress
#
# @see https://github.com/sebastianbergmann/phploc
#
.PHONY: phploc
phploc: DIR?=src
phploc: ## Shows metrics about size and structure or the codebase
	$(call execute_qa_tool_in_external_container, phploc $(DIR) $(ARGS))

#
# =================================================================
# Commit lint
# =================================================================
#
# Verifies that all commits follow the conventional commit message
# guidelines.
#
# @see https://www.conventionalcommits.org/en/v1.0.0/
# @see https://github.com/conventional-changelog/commitlint
#
.PHONY: commitlint
commitlint: ## Check a commit message against our commit message rules. Usage make commitlint MSG="chore(monorepo): is this valid"
	$(if $(MSG),,$(error "Usage: make commitlint MSG=chore(monorepo): is this valid?"))
	$(MAYBE_EXEC_APP_IN_DOCKER) bash -c 'echo "$(MSG)" | yarn commitlint'
	@echo "Valid commit message."

.PHONY: commitlint-from
commitlint-from: ## Checks all commit message after the provided commit sha. Usage: make commitlint-from SHA=4234235423123.
	$(if $(SHA),,$(error "Usage: make commitlint-from: SHA=4234235423123"))
	# We need to append the "^" to the sha so that its also included, not just child commits.
	# In order to start at SHA we need to run commitlint from parent(SHA)
	$(MAYBE_EXEC_APP_IN_DOCKER) yarn commitlint --from "$(SHA)^"
	@echo "All commit messages are valid."

#.PHONY: clear-qa-cache
#clear-qa-cache: ## Clear all caches of QA tools
#	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/psalm --clear-cache || true
#	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/psalm --clear-global-cache || true
#	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/ecs check --clear-cache || true
#	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/rector --dry-run --clear-cache || true






