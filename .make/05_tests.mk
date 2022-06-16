##@ [Tests]

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