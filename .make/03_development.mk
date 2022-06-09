##@ [Development]

.PHONY:npm

npm: ## Run an npm script. Usage: make npm ARGS="run dev"
	$(MAYBE_RUN_NODE_IN_DOCKER) npm ${ARGS}

commit: ## Launch the interactive commit tool
	$(MAYBE_RUN_NODE_IN_DOCKER) npm run commit