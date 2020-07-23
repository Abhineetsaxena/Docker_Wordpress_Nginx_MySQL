FROM abhineet05/php-wordpress-test:assignment

ARG WORK_DIR
WORKDIR $WORK_DIR

RUN chown -R www-data:www-data $WORK_DIR
RUN chmod -R g+w $WORK_DIR
RUN chmod 777 /var/www/try_docker/wp-content
RUN chmod 777 /var/www/try_docker/wp-content/plugins

USER www-data
