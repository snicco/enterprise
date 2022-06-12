ARG NODE_VERSION
ARG ALPINE_VERSION

FROM node:$NODE_VERSION-alpine$ALPINE_VERSION as base

ARG APP_USER_ID
ARG APP_GROUP_ID
ARG APP_USER_NAME
ARG APP_GROUP_NAME

RUN deluser --remove-home node

RUN addgroup -g $APP_GROUP_ID $APP_GROUP_NAME && \
    adduser -D -u $APP_USER_ID -s /bin/bash $APP_USER_NAME -G $APP_GROUP_NAME && \
    mkdir -p /project && \
    chown $APP_USER_NAME: /project

RUN apk --no-cache add git

WORKDIR /project

FROM base as local
