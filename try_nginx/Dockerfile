FROM nginx:stable

EXPOSE 80
EXPOSE 443

COPY ./conf.d/upstream.conf /etc/nginx/conf.d/
COPY ./conf.d/try_docker.conf /etc/nginx/conf.d/
