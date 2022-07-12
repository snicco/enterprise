
### Added by Snicco (https://github.com/snicco/enterprise)

alias m="make"

#
# =================================================================
# Create a shell in any running docker container
# =================================================================
#
# This functions logs into the first container that matches the
# passed argument and creates a shell.
#
# din CONTAINER <user> <shell type>
#
function din() {
  SERVICE=$1

  USER=()
  if [[ -n "$2" ]];
  then
    USER=("${USER[@]}" --user "$2")
  fi

  SHELL="/bin/sh"
  if [[ -n "$3" ]];
  then
    SHELL=$3
  fi

  docker exec --interactive --tty "${USER[@]}" "$(docker ps --filter name="${SERVICE}" -q | head -1)" "${SHELL}"
}

### End Snicco
