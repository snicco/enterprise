##@ [Docker]

# For local builds we always want to use "latest" as tag per default
ifeq ($(ENV),local)
	TAG:=latest
endif

#
# =================================================================
# Enable Buildkit for docker
# =================================================================
#
# Export Buildkit settings for docker and docker-compose to
# subshells.
# For specific environments (e.g. MacBook with Apple Silicon M1 CPU)
# it should be turned off to work stable.
# This can be done by configuring the local make/.env file.
#
COMPOSE_DOCKER_CLI_BUILD?=1
DOCKER_BUILDKIT?=1
export COMPOSE_DOCKER_CLI_BUILD
export DOCKER_BUILDKIT

#
# =================================================================
# Define docker variables
# =================================================================
#
DOCKER_DIR:=./.docker
DOCKER_ENV_FILE:=$(DOCKER_DIR)/.env
DOCKER_COMPOSE_DIR:=$(DOCKER_DIR)/docker-compose
DOCKER_COMPOSE_FILE:=$(DOCKER_COMPOSE_DIR)/docker-compose.yml
DOCKER_COMPOSE_PROJECT_NAME:=snicco_enterprise_$(ENV)

#
# =================================================================
# Define docker container names
# =================================================================
#
# These values must match exactly the container names
# in the docker-compose files.
#
DOCKER_SERVICE_NODE_NAME:=node

#
# =================================================================
# Define a docker compose macro
# =================================================================
#
# Build a make macro so that we dont have to type very
# long commands all the time.
#
# This will export the current environment variables and then
# run docker composer.
#
_DOCKER_COMPOSE_COMMAND:=ENV=$(ENV) \
 TAG=$(TAG) \
 DOCKER_REGISTRY=$(DOCKER_REGISTRY) \
 DOCKER_NAMESPACE=$(DOCKER_NAMESPACE) \
 APP_USER_NAME=$(APP_USER_NAME) \
 APP_GROUP_NAME=$(APP_GROUP_NAME) \
 APP_USER_ID=$(APP_USER_ID) \
 APP_GROUP_ID=$(APP_GROUP_ID) \
 docker-compose -p $(DOCKER_COMPOSE_PROJECT_NAME) --env-file $(DOCKER_ENV_FILE)

DOCKER_COMPOSE:=$(_DOCKER_COMPOSE_COMMAND) -f $(DOCKER_COMPOSE_FILE)

#
# =================================================================
# Are we running make inside OUR docker container?
# =================================================================
#
# This macro will determine if we are currently running
# inside a docker container.
#
# This is convenient and will allow use to use make inside docker.
#
# If FORCE_RUN_IN_CONTAINER=true is passed we will always run
# commands inside new docker containers.
#
# This is needed because for example Github Actions is run in docker
# but we need to run in OUR docker container.
#
FORCE_RUN_IN_CONTAINER?=
MAYBE_RUN_NODE_IN_DOCKER?=

ifndef FORCE_RUN_IN_CONTAINER
	# check if 'make' is executed in a docker container,
	# @see https://stackoverflow.com/a/25518538/413531
	# `wildcard $file` checks if $file exists,
	# @see https://www.gnu.org/software/make/manual/html_node/Wildcard-Function.html
	# i.e. if the result is "empty" then $file does NOT exist => we are NOT in a container
	ifeq ("$(wildcard /.dockerenv)","")
		FORCE_RUN_IN_CONTAINER=true
	endif
endif
ifeq ($(FORCE_RUN_IN_CONTAINER),true)
	MAYBE_RUN_NODE_IN_DOCKER:=$(DOCKER_COMPOSE) run --rm $(DOCKER_SERVICE_NODE_NAME)
endif

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
	@echo "All docker variables are set."


#
# =================================================================
# Genereal purpose docker commands
# =================================================================
#
# The following docker commands will all run automatically
# with the correct environment, location, etc.
#
# This works by using the DOCKER_COMPOSER macro we defined above.
#

.PHONY: docker-config
docker-config: _validate-docker-env ## List the merged configuration for current environment.
	@$(DOCKER_COMPOSE) config

.PHONY: docker-prune
docker-prune: ## Remove ALL unused docker resources, including volumes and images.
	@docker system prune -a -f --volumes

.PHONY:docker-image
docker-image: SERVICE_NAME?=
docker-image: _validate-docker-env ## Build one or more docker image(s). Usage: make docker-build-image SERVICE_NAME=<service>.
	$(DOCKER_COMPOSE) build $(SERVICE_NAME)

.PHONY: docker-up
docker-up: SERVICE_NAME?=
docker-up: _validate-docker-env ## Create one or more docker container(s). Usage make docker-up SERVICE_NAME=<service>
	$(DOCKER_COMPOSE) up $(SERVICE_NAME)

.PHONY: docker-down
docker-down: _validate-docker-env ## Stop and remove docker all containers.
	@$(DOCKER_COMPOSE) down
