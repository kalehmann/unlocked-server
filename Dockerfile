ARG PHP_VERSION=8.1

FROM php:${PHP_VERSION}-fpm-alpine AS ul_php

RUN wget -O - https://github.com/symfony-cli/symfony-cli/releases/download/v5.4.2/symfony-cli_linux_amd64.tar.gz | tar xzf - -C /usr/local/bin/ symfony \
    && chmod +x /usr/local/bin/symfony

ENTRYPOINT ["/usr/local/bin/symfony"]
CMD ["server:start"]
