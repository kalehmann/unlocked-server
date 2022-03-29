ARG GID=1000
ARG UID=1000
ARG NODE_VERSION=17
ARG PHP_VERSION=8.1

FROM node:${NODE_VERSION}-alpine AS ul_node
ARG GID
ARG UID

RUN sed -i "s/node:x:1000:1000/node:x:${UID}:${GID}/g" /etc/passwd \
    && sed -i "s/node:x:1000:node/node:x:${GID}:node/g" /etc/group \
    && chown -vR ${UID}:${GID} /home/node \
    && mkdir -p /application \
    && chown -R ${UID}:${GID} /application

USER node:node

FROM php:${PHP_VERSION}-fpm-alpine AS ul_php
ARG GID
ARG UID

RUN sed -i "s/www-data:x:82:82/www-data:x:${UID}:${GID}/g" /etc/passwd \
    && sed -i "s/www-data:x:82:www-data/www-data:x:${GID}:www-data/g" /etc/group \
    && chown -vR ${UID}:${GID} /home/www-data \
    && mkdir -p /application/var \
    && mkdir -p /application/vendor \
    && chown -R ${UID}:${GID} /application

RUN wget -O - https://github.com/symfony-cli/symfony-cli/releases/download/v5.4.2/symfony-cli_linux_amd64.tar.gz | tar xzf - -C /usr/local/bin/ symfony \
    && chmod +x /usr/local/bin/symfony \
    && /usr/local/bin/symfony server:ca:install \
    && cp -R /root/.symfony5 /home/www-data \
    && chown -R www-data:www-data /home/www-data

ADD docker/php/install_composer.sh /install_composer.sh
RUN sh /install_composer.sh && rm -f /install_composer.sh


USER www-data:www-data

ENTRYPOINT ["/usr/local/bin/symfony"]
CMD ["server:start"]
