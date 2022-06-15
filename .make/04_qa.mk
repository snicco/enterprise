##@ [Quality Assurance]

.PHONY: commitlint commitlint-from unit-tests wpunit-tests usecase-tests integration-tests cli-tests browser-tests tests lint lint-fix psalm


#
# =================================================================
# Commitlint
# =================================================================
#
# Lint commit messages so that they comply with semantic release.
#

commitlint: ## Check a commit message against our commit message rules. Usage make commitlint MSG="chore(monorepo): is this valid"
	@$(if $(MSG),,$(error "Usage: make commitlint: MSG=chore(monorepo): is this valid?"))
	$(MAYBE_EXEC_NODE_IN_DOCKER) echo ${MSG} | npx commitlint

commitlint-from: ## Checks all commit message after the provided commit sha. Usage: make commitlint-from COMMIT_SHA=4234235423123.
	@$(if $(COMMIT_SHA),,$(error "Usage: make commitlint-from: COMMIT_SHA=4234235423123"))
	$(MAYBE_EXEC_NODE_IN_DOCKER) npx commitlint --from ${COMMIT_SHA}

tests: unit-tests wpunit-tests usecase-tests integration-tests cli-tests browser-tests ## Run all tests for all packages
	echo "All tests done."

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

lint: ## Lint the codebase without applying fixes
	# $(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/rector process --dry-run # @todo Enable rector once its compatible with codeception
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/ecs check

lint-fix:
	# $(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/rector process # @todo Enable rector once its compatible with codeception
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/ecs --fix

psalm:
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/psalm