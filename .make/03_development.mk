##@ [Development]

.PHONY:npm node commit php wp dev-server sync-wp

dev-server: setup ## Start all development containers.
	$(MAKE) docker-up
	@echo "Development server is running at https://snicco-enterprise.test"
	$(MAKE) sync-wp

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

php: ## Run any php script in the app container. Usage: make php ARGS="foo.php"
	$(MAYBE_RUN_APP_IN_DOCKER) php ${ARGS}

wp: ARGS?=cli version
wp: ## Run a wp-cli command in the wp container. Usage: make wp ARGS="plugin list"
	docker exec -it --user $(APP_USER_NAME) wp wp ${ARGS}

sync-wp:  ## Get a fresh copy of all WordPress files in the wp container.
	@docker cp wp:/var/www/html/ .wp
	@echo "WordPress files have been copied to .wp/html"

