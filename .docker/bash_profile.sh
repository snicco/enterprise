
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
  filter=$1

  user=""
  if [[ -n "$2" ]];
  then
    user="--user $2"
  fi

  shell="/bin/sh"
  if [[ -n "$3" ]];
  then
    shell=$3
  fi

  docker exec -it ${user} $(docker ps --filter name=${filter} -q | head -1) ${shell}
}
### End Snicco