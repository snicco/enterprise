##@ [Development]

.PHONY:npm node commit php wp dev-server get-files merge

dev-server: update ## Start all development containers.
	$(MAKE) docker-up
	@echo "Development server is running at https://$(APP_HOST)"
	$(MAKE) get-files

node: ARGS?=node -v
node: ## Run any script in the node container. Usage: make npm ARGS="npm run dev".
	@$(MAYBE_EXEC_NODE_IN_DOCKER) ${ARGS}

npm: ## Run any npm script in the node container. Usage: make npm ARGS=dev".
	$(MAYBE_EXEC_NODE_IN_DOCKER) npm run ${ARGS}

commit:  ## Launch the interactive commit tool (node required locally).
	@if ! command -v npm &> /dev/null; \
    then \
        printf "$(RED)'make commit' can currently not run in docker and requires Node.js + npm on your local machine.\n$(NO_COLOR)";\
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

.PHONY: xdebug-on
xdebug-on: SERVICE?=$(DOCKER_SERVICE_PHP_FPM_NAME)
xdebug-on: APP_USER_NAME=root
xdebug-on: ## Enable xdebug in the a container. Usage: make xdebug-on SERVICE=app
	@$(if $(SERVICE),,$(error SERVICE is undefined.))
	@$(MAYBE_EXEC_IN_DOCKER) sed -i 's/.*zend_extension=xdebug/zend_extension=xdebug/' '/usr/local/etc/php/conf.d/zz-app.ini'
	@if [ $(SERVICE) = $(DOCKER_SERVICE_PHP_FPM_NAME) ]; \
	then\
      	$(MAKE) restart-php-fpm;\
    fi
	@echo "XDebug is now enabled in the $(SERVICE) container."

.PHONY: xdebug-off
xdebug-off: SERVICE?=$(DOCKER_SERVICE_PHP_FPM_NAME)
xdebug-off: APP_USER_NAME=root
xdebug-off: ## Disable xdebug in a container.
	@$(if $(SERVICE),,$(error SERVICE is undefined.))
	@$(MAYBE_EXEC_IN_DOCKER) sed -i 's/.*zend_extension=xdebug/;zend_extension=xdebug/' '/usr/local/etc/php/conf.d/zz-app.ini'
	@if [ $(SERVICE) = $(DOCKER_SERVICE_PHP_FPM_NAME) ]; then \
      	$(MAKE) restart-php-fpm; \
    fi
	@echo "XDebug is now disabled in the $(SERVICE) container."

.PHONY: xdebug-path
xdebug-path: ## Get the path to the xdebug extension in the app container.
	@$(MAYBE_EXEC_APP_IN_DOCKER) sh -c 'echo "$$(php-config --extension-dir)/xdebug.so"'

# @see https://stackoverflow.com/a/43076457
.PHONY: restart-php-fpm
restart-php-fpm: ## Restart php-fpm without killing the container.
	@$(DOCKER_COMPOSE) exec --user $(APP_USER_NAME) $(DOCKER_SERVICE_PHP_FPM_NAME) kill -USR2 1
	@echo "PHP-FPM restarted."


