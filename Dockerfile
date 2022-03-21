ARG PHP_VERSION=8.1

FROM php:${PHP_VERSION}-fpm-alpine AS ul_php

RUN wget -O - https://github.com/symfony-cli/symfony-cli/releases/download/v5.4.2/symfony-cli_linux_amd64.tar.gz | tar xzf - -C /usr/local/bin/ symfony \
    && chmod +x /usr/local/bin/symfony \
    && /usr/local/bin/symfony server:ca:install \
    && cp -R /root/.symfony5 /home/www-data \
    && chown -R www-data:www-data /home/www-data

ADD docker/php/install_composer.sh /install_composer.sh
RUN sh /install_composer.sh && rm -f /install_composer.sh

RUN mkdir -p /application/var \
    && chown -R www-data:www-data /application/var

USER www-data:www-data

ENTRYPOINT ["/usr/local/bin/symfony"]
CMD ["server:start"]
