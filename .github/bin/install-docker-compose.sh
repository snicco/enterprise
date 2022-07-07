#!/usr/bin/env bash

set -e
set -o pipefail

if [ -z "$DOCKER_CONFIG" ]; then
    echo "DOCKER_CONFIG is empty"
    exit 1
fi

if [ "$CONFIRM_IS_GITHUB" != 1 ]; then
    echo "install-docker-compose.sh should only be run in GitHub actions."
    exit 1
fi

mkdir -p "$DOCKER_CONFIG"/cli-plugins
curl -sSL https://github.com/docker/compose/releases/download/v2.6.0/docker-compose-linux-$(uname -m) -o "$DOCKER_CONFIG"/cli-plugins/docker-compose
chmod +x "$DOCKER_CONFIG"/cli-plugins/docker-compose
docker compose version
docker --version
docker buildx version