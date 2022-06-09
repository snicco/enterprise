##@ [Development]

.PHONY:npm node commit php

node: ## Run any script in the node container. Usage: make npm ARGS="npm run dev".
	$(MAYBE_RUN_NODE_IN_DOCKER) ${ARGS}

npm: ## Run any npm script in the node container. Usage: make npm ARGS=dev".
	$(MAYBE_RUN_NODE_IN_DOCKER) npm run ${ARGS}

commit:  ## Launch the interactive commit tool (node required locally).
	@if ! command -v npm &> /dev/null; \
    then \
        printf "This make target can currently not run in docker and requires node + npm on your local machine.\n";\
        exit 1;\
    fi
	npm run commit;

php: ## Run any script in the php container. Usage; make php ARGS="foo.php"
	$(MAYBE_RUN_APP_IN_DOCKER) php ${ARGS}