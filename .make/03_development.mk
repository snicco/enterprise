##@ [Development]

.PHONY: dev-server
dev-server: update ## Start all development containers.
	$(MAKE) docker-up
	@echo "Development server is running at https://$(APP_HOST)"
	$(MAKE) get-wp-files

.PHONY: snicco
snicco: COMMAND?=--help
snicco: ARGS?=
snicco:
	$(MAYBE_EXEC_APP_IN_DOCKER) php bin/snicco.php $(COMMAND) $(ARGS)

.PHONY: clean-vendor
clean-vendor: clean-packages-vendor ## Remove all vendor folders.
	rm -rf vendor

.PHONY: clean-packages-vendor
clean-packages-vendor: ## Remove all vendor folders from packages.
	rm -rf src/Snicco/*/*/vendor

.PHONY: node
node: ARGS?=node -v
node: ## Run any script in the node container. Usage: make node ARGS="yarn workspaces info".
	$(MAYBE_EXEC_NODE_IN_DOCKER) $(ARGS)

.PHONY: yarn
yarn: ARGS?=-v
yarn: ## Run any npm script in the node container. Usage: make yarn ARGS="run commit".
	$(MAYBE_EXEC_NODE_IN_DOCKER) yarn $(ARGS)

.PHONY: commit
commit:  ## Launch the interactive commit tool (node required locally).
	@if ! command -v npm &> /dev/null; \
    then \
        printf "$(RED)'make commit' can currently not run in docker and requires Node.js + npm on your local machine.\n$(NO_COLOR)";\
        exit 1;\
    fi
	yarn commit;

.PHONY: php
php: ARGS?=--help
php: ## Run any php script in the app container. Usage: make php ARGS="foo.php"
	$(MAYBE_EXEC_APP_IN_DOCKER) php ${ARGS}

.PHONY: composer
composer: ARGS?=-v
composer: ## Run any composer script in the app container. Usage: make composer ARGS="install"
	$(MAYBE_EXEC_APP_IN_DOCKER) composer ${ARGS}

.PHONY: wp
wp: ARGS?=cli version
wp: ## Run a wp-cli command in the wp container. Usage: make wp ARGS="plugin list"
	docker exec -it --user $(APP_USER_NAME) wp wp ${ARGS}

.PHONY: get-wp-files
get-wp-files:  ## Get a fresh copy of all WordPress files in the wp container.
	docker cp wp:$(WP_CONTAINER_WP_APP_PATH) .wp
	@echo "WordPress files have been copied to .wp/html"

.PHONY: composer-merge
composer-merge: ## Merge all composer.json files of all packages
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/monorepo-builder merge --ansi
	$(MAYBE_EXEC_APP_IN_DOCKER) sed -i 's#"url": "../../#"url": "src/Snicco/#g' 'composer.json'
	$(MAYBE_EXEC_APP_IN_DOCKER) composer update --ansi --no-install
	$(MAKE) composer.json-normalize ARGS="--diff --no-check-lock --indent-size=2 --indent-style=space"
	$(MAYBE_EXEC_APP_IN_DOCKER) composer dump-autoload --ansi

.PHONY: composer-propagate
composer-propagate: ## Propagate dependencies from the main composer.json to packages
	$(MAYBE_EXEC_APP_IN_DOCKER) vendor/bin/monorepo-builder propagate --ansi
	$(MAKE) composer-normalize --jobs $(CORES) --output-sync NORMALIZE_ARGS="--diff --no-check-lock"

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

PLUGINS_SRC=$(wildcard src/Snicco/plugin/*)
PLUGIN_BUILDS=$(wildcard ./.build/plugins/*)

PROD_BUILD_COMMAND=@$(if $(BUILD_VERSION),,$(error BUILD_VERSION is undefined.)) \
                   $(MAYBE_EXEC_APP_IN_DOCKER) sh .docker/images/app/bin/build_plugin.sh $@ .build/plugins/$(subst src/Snicco/plugin/,,$@) $(subst src/Snicco/plugin/,,$@)-$(BUILD_VERSION)
#                   $(MAYBE_EXEC_NODE_IN_DOCKER) sh .docker/images/node/bin/build_plugin_assets.sh $@ .build/plugins/$(subst src/Snicco/plugin/,,$@)

ifdef FORCE_PROD_BUILD
	BUILD_COMMAND=$(PROD_BUILD_COMMAND)
endif

ifndef BUILD_COMMAND
	ifeq ($(ENV),local)
		BUILD_COMMAND=$(MAYBE_EXEC_APP_IN_DOCKER) composer update --working-dir=$@ $(ARGS)
	else
		BUILD_COMMAND=$(PROD_BUILD_COMMAND)
	endif
endif

.PHONY: build-dev
build-dev: ## Build all plugins based on the current environment.
	$(MAYBE_EXEC_APP_IN_DOCKER) yarn workspaces foreach --interlaced --verbose --parallel --jobs $(CORES) run build-dev

.PHONY: build-prod
build-prod: ## Build all plugins based on the current environment.
	$(MAYBE_EXEC_APP_IN_DOCKER) yarn workspaces foreach --interlaced --verbose --parallel --jobs $(CORES) run build-prod $(MONOREPO_ROOT)/.build/plugins

.PHONY: copy-prod-plugins $(PLUGIN_BUILDS)
copy-prod-plugins: $(PLUGIN_BUILDS) ## Copy built production plugins into the WordPress container (CI only).
$(PLUGIN_BUILDS): _is_ci
	$(eval OUTPUT_DIR := /var/www/html/wp-content/plugins/$(subst .build/plugins/,,$@))
	docker cp $@ $(DOCKER_SERVICE_PHP_FPM_NAME):$(OUTPUT_DIR)
	docker exec --user root $(DOCKER_SERVICE_PHP_FPM_NAME) chown -R $(APP_USER_NAME):$(APP_GROUP_NAME) $(OUTPUT_DIR)
