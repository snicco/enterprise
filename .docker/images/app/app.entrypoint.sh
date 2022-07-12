#!/usr/bin/env bash
set -e

chown -R "$APP_USER_NAME:$APP_USER_GROUP" "$MONOREPO_PATH"

"$@"