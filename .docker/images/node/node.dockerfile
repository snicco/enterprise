ARG NODE_VERSION
ARG ALPINE_VERSION

FROM node:$NODE_VERSION-alpine$ALPINE_VERSION as base

#
# =================================================================
# Install Git
# =================================================================
#
# Git is needed for both commitizen and commitlint in this
# container.
#
RUN apk --no-cache add git

ARG APP_USER_ID
ARG APP_GROUP_ID
ARG APP_USER_NAME
ARG APP_GROUP_NAME
ARG MONOREPO_PATH

#
# =================================================================
# Create custom user and delete node.js user
# =================================================================
#
# The official node.js image creates a "node" user which
# has a defeault id of 1000 which collides with some local user ids.
# This user can be savely deleted as it is not required in any
# way by the node image.
#
# @see https://github.com/nodejs/docker-node/blob/main/docs/BestPractices.md#non-root-user
#
RUN deluser --remove-home node && \
    addgroup -g $APP_GROUP_ID $APP_GROUP_NAME && \
    adduser -D -u $APP_USER_ID -s /bin/bash $APP_USER_NAME -G $APP_GROUP_NAME && \
    mkdir -p $MONOREPO_PATH && \
    chown $APP_USER_NAME: $MONOREPO_PATH

RUN corepack enable && \
    yarn set version "3.2.1"

WORKDIR $MONOREPO_PATH

FROM base as local

FROM base as ci

#ENV CI=1
#
#COPY package.json $MONOREPO_PATH
#COPY package-lock.json $MONOREPO_PATH
#
#RUN npm ci

COPY --chown=$APP_USER_NAME:$APP_GROUP_NAME . $MONOREPO_PATH

RUN yarn