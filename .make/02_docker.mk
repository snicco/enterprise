##@ [Docker]

#
# =================================================================
# Enable BuildKit for docker
# =================================================================
#
# Export BuildKit settings for docker and docker-compose to
# subshells.
#
COMPOSE_DOCKER_CLI_BUILD?=1
DOCKER_BUILDKIT?=1
BUILDKIT_INLINE_CACHE?=1
export COMPOSE_DOCKER_CLI_BUILD
export DOCKER_BUILDKIT
export BUILDKIT_INLINE_CACHE

#
# =================================================================
# Define docker variables
# =================================================================
#
DOCKER_DIR:=./.docker
DOCKER_ENV_FILE:=$(DOCKER_DIR)/.env
DOCKER_COMPOSE_DIR:=$(DOCKER_DIR)/compose
ROOT_DOCKER_COMPOSE_FILE:=$(DOCKER_COMPOSE_DIR)/docker-compose.yml
ROOT_DOCKER_COMPOSE_FILE_LOCAL:=$(DOCKER_COMPOSE_DIR)/docker-compose.local.yml
ROOT_DOCKER_COMPOSE_FILE_CI:=$(DOCKER_COMPOSE_DIR)/docker-compose.ci.yml
 # Make this a lazy variable so that we can override it from the command line.
DOCKER_COMPOSE_PROJECT_NAME?=snicco_enterprise_$(ENV)

#
# =================================================================
# Parse compose files for environment
# =================================================================
#
# We need to "assemble" the correct combination and order of
# docker-compose files depending on the environment
#
ifeq ($(ENV),local)
	ALL_DOCKER_COMPOSE_FILES:=-f $(ROOT_DOCKER_COMPOSE_FILE) -f $(ROOT_DOCKER_COMPOSE_FILE_LOCAL)
else ifeq ($(ENV),ci)
	ALL_DOCKER_COMPOSE_FILES:=-f $(ROOT_DOCKER_COMPOSE_FILE) -f $(ROOT_DOCKER_COMPOSE_FILE_CI)
endif

#
# =================================================================
# Define docker container names
# =================================================================
#
# These values must match exactly the service names
# in the docker-compose files.
#
DOCKER_SERVICE_APP_NAME:=app
DOCKER_SERVICE_PHP_FPM_NAME:=wp

#
# =================================================================
# Define a docker compose macro
# =================================================================
#
# Build a make macro so that we dont have to type very
# long commands all the time.
#
# This will export the current environment variables and then
# run docker compose.
#
DOCKER_COMPOSE=ENV=$(ENV) \
 TAG=$(TAG) \
 DOCKER_REGISTRY=$(DOCKER_REGISTRY) \
 DOCKER_NAMESPACE=$(DOCKER_NAMESPACE) \
 APP_USER_NAME=$(APP_USER_NAME) \
 APP_GROUP_NAME=$(APP_GROUP_NAME) \
 APP_USER_ID=$(APP_USER_ID) \
 APP_GROUP_ID=$(APP_GROUP_ID) \
 COMPOSER_AUTH_JSON_PATH=$(COMPOSER_AUTH_JSON_PATH) \
 COMPOSER_CACHE_PATH=$(COMPOSER_CACHE_PATH) \
 APP_HOST=$(APP_HOST) \
 WP_CONTAINER_WP_APP_PATH=$(WP_CONTAINER_WP_APP_PATH) \
 APP_CONTAINER_MONOREPO_PATH=$(APP_CONTAINER_MONOREPO_PATH) \
 SNICCO_QA_CACHE_DIR=$(SNICCO_QA_CACHE_DIR) \
 docker compose -p $(DOCKER_COMPOSE_PROJECT_NAME) --env-file $(DOCKER_ENV_FILE) $(ALL_DOCKER_COMPOSE_FILES)

#
# =================================================================
# Are we running make inside OUR docker container?
# =================================================================
#
# This macro will determine if we are currently running
# inside a docker container.
#
# This is convenient and will allow use to use our make commands inside
# a docker container.
#
# If FORCE_RUN_IN_CONTAINER=true is passed we will always run
# commands inside new docker containers.
#
FORCE_RUN_IN_CONTAINER?=
MAYBE_RUN_APP_IN_DOCKER?=
MAYBE_EXEC_IN_DOCKER?=
MAYBE_EXEC_APP_IN_DOCKER?=
DOCKER_EXEC_ARGS?=

ifeq ($(ENV),ci)
	DOCKER_EXEC_ARGS+= --no-TTY # avoid "the input device is not a TTY" in GitHub Actions
endif

ifndef FORCE_RUN_IN_CONTAINER
	# check if 'make' is executed in a docker container,
	# @see https://stackoverflow.com/a/25518538/413531
	# `wildcard $file` checks if $file exists,
	# @see https://www.gnu.org/software/make/manual/html_node/Wildcard-Function.html
	# i.e. if the result is "empty" then $file does NOT exist => we are NOT in a container
	ifeq ("$(wildcard /.dockerenv)","")
		FORCE_RUN_IN_CONTAINER=1
	endif
endif
ifeq ($(FORCE_RUN_IN_CONTAINER),1)
	# These variables need to be lazy so that the arguments can be customized from targets.
	MAYBE_RUN_APP_IN_DOCKER=$(DOCKER_COMPOSE) run --user $(APP_USER_NAME) --rm $(DOCKER_SERVICE_APP_NAME)
	MAYBE_EXEC_IN_DOCKER=$(DOCKER_COMPOSE) exec $(DOCKER_EXEC_ARGS) --user $(APP_USER_NAME) $(SERVICE)
	MAYBE_EXEC_APP_IN_DOCKER=$(DOCKER_COMPOSE) exec $(DOCKER_EXEC_ARGS) --user $(APP_USER_NAME) $(DOCKER_SERVICE_APP_NAME)
endif

#
# =================================================================
# General purpose docker commands
# =================================================================
#
# The following docker commands will all run automatically
# with the correct environment, location, etc.
#
# This works by using the DOCKER_COMPOSER macro we defined above.
#
.PHONY: docker-config
docker-config: _validate-docker-env ## List the merged configuration for current environment.
	$(DOCKER_COMPOSE) config

.PHONY: docker-build
docker-build: SERVICE?=
docker-build: _validate-docker-env ## Build one or more docker image(s). Usage: make docker-build-image SERVICE=<service...>.
	$(DOCKER_COMPOSE) build $(SERVICE) $(ARGS)

.PHONY: docker-up
docker-up: SERVICE?=
docker-up: MODE?=--build --detach
docker-up: _validate-docker-env ## Create one or more docker container(s). Usage make docker-up SERVICE=<service...>
	$(DOCKER_COMPOSE) up $(MODE) $(SERVICE)

.PHONY: docker-down
docker-down: _validate-docker-env ## Remove docker all containers from the current project.
	$(DOCKER_COMPOSE) down $(ARGS)

.PHONY: docker-down-all
docker-down-all: _validate-docker-env ## Remove docker all containers.
	docker rm -f $$(docker ps -a -q) || true

.PHONY: docker-restart
docker-restart: docker-down docker-up ## Restart all containers.

.PHONY: docker-run
docker-run: SERVICE?=
docker-run: COMMAND?=/bin/sh DOCKER_EXEC_ARGS?=-i
docker-run: _validate-docker-env ## Run a command inside a docker container. Usage make docker-run SERVICE=app COMMAND="php -v".
	$(DOCKER_COMPOSE) run --user $(APP_USER_NAME) $(DOCKER_EXEC_ARGS) --rm  $(SERVICE) $(COMMAND)

.PHONY: docker-push
docker-push: SERVICE?=
docker-push: _validate-docker-env _is_ci ## Push image to a remote registry.
	$(DOCKER_COMPOSE) push $(SERVICE)

.PHONY: docker-pull
docker-pull: SERVICE?=
docker-pull: _validate-docker-env ## Push image to a remote registry.
	$(DOCKER_COMPOSE) pull $(SERVICE)

.PHONY: docker-prune-v
docker-prune-v: _validate-docker-env docker-down-all ## Delete all docker volumes.
	docker volume prune -f

.PHONY: docker-prune
docker-prune: docker-down-all ## Remove ALL docker resources, including volumes and images.
	@docker system prune -a -f --volumes

.PHONY: docker-copy
docker-copy: ## Copy files from a docker container to the host.
	@$(if $(SERVICE),,$(error SERVICE is undefined))
	@$(if $(FROM),,$(error FROM is undefined))
	@$(if $(TO),,$(error TO is undefined))
	$(DOCKER_COMPOSE) cp $(SERVICE):$(FROM) $(TO)

.PHONY: docker-copy-vendor
docker-copy-vendor: _is_ci ## Copy the vendor folder from the app service to the host.
	$(MAKE) SERVICE=$(DOCKER_SERVICE_APP_NAME) FROM=$(APP_CONTAINER_MONOREPO_PATH)/vendor TO=vendor docker-copy --no-print-directory

#
# =================================================================
# Internal commands
# =================================================================
#
.PHONY: dvp
dvp: docker-prune-v

.PHONY: _is_ci
_is_ci:
	@if [ $(ENV) != ci ]; then \
            printf "$(RED) make '$(MAKECMDGOALS)' can only be run with ENV=ci.\n$(NO_COLOR)";\
            exit 1;\
    fi

.PHONY: _is_local
_is_local:
	@if [ $(ENV) != local ]; then \
            printf "$(RED) make '$(MAKECMDGOALS)' can only be run with ENV=local.\n$(NO_COLOR)";\
            exit 1;\
    fi

.PHONY: _validate-docker-env
_validate-docker-env:
	@$(if $(TAG),,$(error TAG is undefined - - Did you run make setup?))
	@$(if $(ENV),,$(error ENV is undefined - Did you run make setup?))
	@$(if $(APP_USER_NAME),,$(error APP_USER_NAME is undefined - Did you run make setup?))
	@$(if $(APP_GROUP_NAME),,$(error APP_GROUP_NAME is undefined - Did you run make setup?))
	@$(if $(APP_USER_ID),,$(error APP_USER_ID is undefined - Did you run make setup?))
	@$(if $(APP_GROUP_ID),,$(error APP_GROUP_ID is undefined - Did you run make setup?))
	@$(if $(DOCKER_REGISTRY),,$(error DOCKER_REGISTRY is undefined - Did you run make setup?))
	@$(if $(DOCKER_NAMESPACE),,$(error DOCKER_NAMESPACE is undefined - Did you run make setup?))
	@$(if $(COMPOSER_AUTH_JSON_PATH),,$(error COMPOSER_AUTH_JSON_PATH is undefined - Did you run make setup?))
	@$(if $(COMPOSER_CACHE_PATH),,$(error COMPOSER_CACHE_PATH is undefined - Did you run make setup?))
	@$(if $(APP_HOST),,$(error APP_HOST is undefined - Did you run make setup?))
	@$(if $(WP_CONTAINER_WP_APP_PATH),,$(error WP_CONTAINER_WP_APP_PATH is undefined - Did you run make setup?))
	@$(if $(APP_CONTAINER_MONOREPO_PATH),,$(error APP_CONTAINER_MONOREPO_PATH is undefined - Did you run make setup?))
	@$(if $(SNICCO_QA_CACHE_DIR),,$(error SNICCO_QA_CACHE_DIR is undefined - Did you run make setup?))