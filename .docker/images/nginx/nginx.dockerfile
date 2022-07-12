ARG NGINX_VERSION

FROM nginx:${NGINX_VERSION}-alpine as base

#
# =================================================================
# Copy self signed certificates
# =================================================================
#
# These certificates a generated with a make command on setup.
# mkcert is needed locally to trust these certificates.
#
# @see https://github.com/FiloSottile/mkcert
#
COPY ./certs /etc/nginx/certs/self-signed
COPY ./default.conf /etc/nginx/conf.d/default.conf

#
# =================================================================
# Set NGINX web root
# =================================================================
#
# The WordPress files are mapped as a volume in docker-compose.yml
# to the value of APP_CODE_PATH.
# We just need to point our default nginx config to that directory.
#
ARG WP_APPLICATION_PATH
RUN mkdir -p $WP_APPLICATION_PATH && \
    sed -i "s#root __NGINX_ROOT;#root $WP_APPLICATION_PATH;#" /etc/nginx/conf.d/default.conf;

#
# =================================================================
# Ensure NGINX can run as non root
# =================================================================
#
# Like in other containers we dont want to run nginx as root.
# But for nginx is not important that we have a custom user that
# is mapped to the hosts user because the nginx container will not
# write anything to the host machine.
#
# The default nginx image already shipes with an "nginx:nginx" user
# so we will use that one here.
#
RUN touch /var/run/nginx.pid && \
    chown -R nginx:nginx $WP_APPLICATION_PATH && \
    chown -R nginx:nginx /var/cache/nginx && \
    chown -R nginx:nginx /etc/nginx/certs && \
    chown -R nginx:nginx /var/run/nginx.pid && \
    sed -i "s#user nginx;;#user nginx;#" /etc/nginx/nginx.conf

USER nginx

FROM base as local

FROM base as ci