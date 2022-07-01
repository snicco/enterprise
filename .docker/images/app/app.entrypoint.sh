#!/usr/bin/env bash
set -e

set -o allexport; source tests/.env.testing; set +o allexport

"$@"