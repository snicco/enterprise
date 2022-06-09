FROM nginx:alpine

ADD ./default.conf etc/nginx/conf.d/default.conf

# You need to use mkcert locally to create a trusted authority
ADD ./certs etc/nginx/certs/self-signed

