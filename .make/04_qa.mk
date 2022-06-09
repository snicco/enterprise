##@ [Quality Assurance]

#
# =================================================================
# Commitlint
# =================================================================
#
# Lint commit messages so that they comply with semantic release.
#

.PHONY:commitlint
commitlint: ## Check a commit message against our commit message rules. Usage make commitlint MSG="chore(monorepo): is this valid
	@$(if $(MSG),,$(error "Usage: make commitlint: MSG=chore(monorepo): is this valid?"))
	$(MAYBE_RUN_NODE_IN_DOCKER) echo ${MSG} | npx commitlint

.PHONY:commitlint-from
commitlint-from: ## Checks all commit message after the provided commit sha. Usage: make commitlint-from COMMIT_SHA=4234235423123
	@$(if $(COMMIT_SHA),,$(error "Usage: make commitlint-from: COMMIT_SHA=4234235423123"))
	$(MAYBE_RUN_NODE_IN_DOCKER) npx commitlint --from ${COMMIT_SHA}