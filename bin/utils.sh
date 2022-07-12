#!/usr/bin/env bash

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[0;33m'
NC='\033[0m'

function debug() {
  echo -e "$*" >&2
}

function error() {
  debug ""
  echo -e "${RED}[ERROR] $*${NC}" >&2
  debug ""
}

function success() {
  debug ""
  echo -e "${GREEN}[OK] $*${NC}" >&2
  debug ""
}

function heading() {
  debug ""
  debug "$YELLOW $1"
  debug "=================================================================$NC"
  debug ""
}

#
#
# ARRAY=(these are some words)
# if inArray words "${ARRAY[@]}"; then
# ...
# fi
inArray() {
  local word=$1
  shift
  for e in "$@"; do [[ "$e" == "$word" ]] && return 0; done
  return 1
}
