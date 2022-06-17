##@ [Development]

.PHONY:npm node commit php wp dev-server get-files merge

dev-server: update ## Start all development containers.
	$(MAKE) docker-up
	@echo "Development server is running at https://$(APP_HOST)"
	$(MAKE) get-files

node: ARGS?=node -v
node: ## Run any script in the node container. Usage: make npm ARGS="npm run dev".
	$(MAYBE_EXEC_NODE_IN_DOCKER) ${ARGS}

npm: ## Run any npm script in the node container. Usage: make npm ARGS=dev".
	$(MAYBE_EXEC_NODE_IN_DOCKER) npm run ${ARGS}

commit:  ## Launch the interactive commit tool (node required locally).
	@if ! command -v npm &> /dev/null; \
    then \
        printf "This make target can currently not run in docker and requires node + npm on your local machine.\n";\
        exit 1;\
    fi
	npm run commit;

php: ARGS?=-v
php: ## Run any php script in the app container. Usage: make php ARGS="foo.php"
	$(MAYBE_EXEC_APP_IN_DOCKER) php ${ARGS}

wp: ARGS?=cli version
wp: ## Run a wp-cli command in the wp container. Usage: make wp ARGS="plugin list"
	docker exec -it --user $(APP_USER_NAME) wp wp ${ARGS}

get-files:  ## Get a fresh copy of all WordPress files in the wp container.
	@docker cp wp:$(WP_CONTAINER_WP_APP_PATH) .wp
	@echo "WordPress files have been copied to .wp/html"

merge: ## Merge all composer.json files of all packages
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/monorepo-builder merge

propagate: ## Propagate dependencies from the main composer.json to packages
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/monorepo-builder propagate
