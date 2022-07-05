#!/usr/bin/env bash

set -e
set -o pipefail

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[0;33m'
NC='\033[0m'

function heading() {
  echo ""
  echo -e "$YELLOW $1"
  echo -e "=================================================================$NC"
  echo ""
}

MAKE_ARGS=-s

if [ "$LOCAL_CI_DEBUG" == 1 ]; then
  MAKE_ARGS=
  docker version
  docker compose version
fi

#
# =================================================================
# Cleanup previous runs
# =================================================================
#
# We need to stop previous containers and also set the environment
# to ci.
#
heading "Cleaning up previous runs"

export ENV=ci

if [ "$LOCAL_CI_SKIP_REBUILD" == 1 ]; then
  echo "Skipped clean up."
else
  make docker-down "$MAKE_ARGS" || true
fi

START_TOTAL=$(date +%s)

#
# =================================================================
# Pull images
# =================================================================
#
# We pull external images (chrome, mariadb) from dockerhub
# and also our internal images from the GH package registry.
# The internal images are used as cache snapshots.
#
# This is on purpose not done inside docker compose build because
# docker compose pull pulls missing images in parallel.
#
heading "Pulling docker images"
START_DOCKER_PULL=$(date +%s)
if [ "$LOCAL_CI_SKIP_REBUILD" == 1 ]; then
  echo "Skipped docker pull."
else
  make docker-pull "$MAKE_ARGS" || true
fi
END_DOCKER_PULL=$(date +%s)

#
# =================================================================
# Build images
# =================================================================
#
# We build all images that we pulled (or have locally).
# There really is no relevant difference between running:
#   1) docker compose build && docker compose up
#   2) make docker-up --build
# which is why we separate them here into two steps for more
# timestamp information.
#
heading "Building docker images"
START_DOCKER_BUILD=$(date +%s)
if [ "$LOCAL_CI_SKIP_REBUILD" == 1 ]; then
  echo "Skipped docker build."
else
  make docker-build "$MAKE_ARGS"
fi
END_DOCKER_BUILD=$(date +%s)

#
# =================================================================
# Start containers
# =================================================================
#
# We need to specify the MODE explicitly here because the default
# in the docker.mk file is "--build --detach"
#
heading "Starting containers"
START_DOCKER_UP=$(date +%s)
make docker-up "$MAKE_ARGS" MODE="--detach"
END_DOCKER_UP=$(date +%s)

#
# =================================================================
# Quality assurance
# =================================================================
#
# We run all QA tools before we run any tests.
#
heading "Running QA tools"
START_QA=$(date +%s)
make qa "$MAKE_ARGS" QUIET=true || FAILED+=QA
END_QA=$(date +%s)

#
# =================================================================
# Test
# =================================================================
#
#
heading "Running test suites of affected packages"
START_TEST=$(date +%s)
make test-affected "$MAKE_ARGS" || FAILED+=", TEST_FAST"
END_TEST=$(date +%s)

#
# =================================================================
# Evaluate results and runtime info
# =================================================================
#
#
END_TOTAL=$(date +%s)

heading "Runtime results"

echo -e "Pull images:         $YELLOW" $((END_DOCKER_PULL - START_DOCKER_PULL))"s$NC"
echo -e "Build docker:        $YELLOW" $((END_DOCKER_BUILD - START_DOCKER_BUILD))"s$NC"
echo -e "Start docker:        $YELLOW" $((END_DOCKER_UP - START_DOCKER_UP))"s$NC"
echo -e "QA:                  $YELLOW" $((END_QA - START_QA))"s$NC"
echo -e "Fast tests:          $YELLOW" $((END_TEST - START_TEST))"s$NC"
echo -e "---------------------------"
echo -e "Total:               $YELLOW" $((END_TOTAL - START_TOTAL))"s$NC"
echo -e ""

if [ -n "$FAILED" ]; then
  echo -e "${RED}[ERROR] One ore more steps failed: [$FAILED].$NC"
else
  echo -e "${GREEN}[SUCCESS] All CI steps passed.$NC"
fi

make docker-down > /dev/null 2>&1

if [ -n "$FAILED" ]; then
  exit 1;
fi