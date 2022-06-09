#
# =================================================================
# Define the default shell
# =================================================================
#
# @see https://stackoverflow.com/a/14777895/413531 for the
# OS detection logic.
#
OS?=undefined
ifeq ($(OS),Windows_NT)
	# Windows requires the .exe extension, otherwise the entry is ignored
	# @see https://stackoverflow.com/a/60318554/413531
    SHELL := bash.exe
else
    SHELL := bash
endif


#
# =================================================================
# Configuring some make best practices
# =================================================================
#
# @see https://tech.davis-hansson.com/p/make/
# @see http://redsymbol.net/articles/unofficial-bash-strict-mode/
# @see https://unix.stackexchange.com/a/179305
#
.SHELLFLAGS := -euo pipefail -c # use bash strict mode
# -e 			- instructs bash to immediately exit if any command has a non-zero exit status
# -u 			- a reference to any variable you haven't previously defined - with the exceptions of $* and $@ - is an error
# -o pipefail 	- if any command in a pipeline fails, that return code will be used as the return code
#				  of the whole pipeline. By default, the pipeline's return code is that of the last command - even if it succeeds.
# -c            - Read and execute commands from string after processing the options. Otherwise, arguments are treated  as filed. Example:
#                 bash -c "echo foo" # will excecute "echo foo"
#                 bash "echo foo"    # will try to open the file named "echo foo" and execute it
MAKEFLAGS += --warn-undefined-variables # display a warning if variables are used but not defined
MAKEFLAGS += --no-builtin-rules # remove some "magic make behavior"


#
# =================================================================
# Include the make env file
# =================================================================
#
# The leading "-" tells make to NOT fail if the file
# does not exist.
#
-include .make/.env

#
# =================================================================
# Display usage instruction
# =================================================================
#
# @see https://www.thapaliya.com/en/writings/well-documented-makefiles/
#
# The description is parsed from the text after an '## ' string on
# the right of the make target.
# The default goal must be defined before other Makefiles
# are included.
#
DEFAULT_GOAL := help
help:
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage:\n  make \033[36m<target>\033[0m\n"} /^[a-zA-Z0-9_.\/-]+:.*?##/ { printf "  \033[36m%-40s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) } ' $(MAKEFILE_LIST)

#
# =================================================================
# Include sub make files
# =================================================================
#
# For better clarity we split make files by their responsibility.
# The files are included by alphabetical order which is why
# we prefix them with numbers.
#
# These makefiles cant be run on their own and targets
# should always be relative to this main Makefile.
#
include make/*.mk

#
# =================================================================
# Define global variables for commands
# =================================================================
#
# Arguments can be added to any make target by running:
# - make <target> ARGS="FOO=BAR"
#
ARGS?=
