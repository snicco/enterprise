##@ [Repository setup]

#
# =================================================================
# Setup the repository locally
# =================================================================
#
.PHONY: setup
init: .make/.mk.env .docker/.env generate-certs ## Initializes the repository.

update: .make/.mk.env .docker/.env generate-certs vendor composer.lock node_modules package-lock.json build-codeception ## Check if all files are still up to date (vendor, node_modules, etc.)

#
# =================================================================
# Install composer dependencies
# =================================================================
#
# If the timestamp of composer.json or composer.lock (if present)
# is newer than the vendor folder we need to install composer
# dependencies.
#
vendor: composer.json $(wildcard composer.lock)
	$(MAYBE_RUN_APP_IN_DOCKER) composer install
	@touch vendor # Need to update file timestamp so that we dont run this again if composer has no new
				 # dependencies.

#
# =================================================================
# Install node dependencies
# =================================================================
#
# If the timestamp of package.json or package.lock (if present)
# is newer than the vendor folder we need to install node
# dependencies.
#
node_modules: package.json $(wildcard package-lock.json)
	$(MAYBE_RUN_NODE_IN_DOCKER) npm install
	@touch node_modules # Need to update file timestamp so that we dont run this again if node has no new
				 # dependencies.

#
# =================================================================
# Update composer dependencies
# =================================================================
#
# If the composer.json file is modified we need to update
# our composer.lock and composer.lock file and dependencies.
#
composer.lock: composer.json
	$(MAYBE_RUN_APP_IN_DOCKER) composer update
	@touch composer.lock # Need to update file timestamp so that we dont run this again if composer has no new
						# dependencies.

#
# =================================================================
# Update node dependencies
# =================================================================
#
# If the package.json file is modified we need to update
# our package.lock file and node dependencies.
#
package-lock.json: package.json
	$(MAYBE_RUN_NODE_IN_DOCKER) npm update
	touch package-lock.json # Need to update file timestamp so that we dont run this again if node has no new
				 # dependencies.

#
# =================================================================
# Create the .env file for docker
# =================================================================
#
.docker/.env: .docker/.env.dist
	@if [ -f .docker/.env ]; \
		then\
			echo 'The .env.dist docker file has changed. Please check your .env docker file and adjust the modified values (This message will not be displayed again)';\
			touch .docker/.env;\
			exit 1;\
		else\
  			cp .docker/.env.dist .docker/.env;\
			echo 'Created new docker .env file.';\
	fi

#
# =================================================================
# Create the .env file for make
# =================================================================
#
.make/.mk.env: .make/.mk.env.dist
	@if [ -f .make/.mk.env ]; \
		then\
			echo 'The .env.dist make file has changed. Please check your .make/.mk.env file and adjust the modified values (This message will not be displayed again)';\
			touch .make/.mk.env;\
			exit 1;\
		else\
  			cp .make/.mk.env.dist .make/.mk.env;\
			echo 'Created new make .env file.';\
	fi

#
# =================================================================
# Create certificates with mkcert
# =================================================================
#
.PHONY: generate-certs
generate-certs:
	@if [ $(ENV) = ci ]; then \
        echo "Skipping mkcert installation in CI..."; \
    else \
        mkcert -install; \
        mkcert -key-file $(DOCKER_DIR)/images/nginx/certs/$(APP_HOST)-key.pem -cert-file $(DOCKER_DIR)/images/nginx/certs/$(APP_HOST).pem $(APP_HOST); \
    fi


.PHONY: build-codeception
build-codeception: ## Build codeception
	$(MAYBE_RUN_APP_IN_DOCKER) vendor/bin/codecept build
