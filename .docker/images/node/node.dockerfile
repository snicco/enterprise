ARG NODE_VERSION
ARG ALPINE_VERSION

FROM node:$NODE_VERSION-alpine$ALPINE_VERSION as base

# We need git for commitlint. But this could also later go into a
# ci stage.
RUN apk --no-cache add git

WORKDIR /project

FROM base as local
