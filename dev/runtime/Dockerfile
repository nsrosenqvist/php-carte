FROM php:8.2-cli-alpine3.20 as base

RUN  --mount=type=bind,from=mlocati/php-extension-installer:2.2,source=/usr/bin/install-php-extensions,target=/usr/local/bin/install-php-extensions \
    install-php-extensions xdebug xhprof ast pcntl && \
    apk del --no-cache ${PHPIZE_DEPS} ${BUILD_DEPENDS}

# Configure xdebug
RUN echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN echo "xdebug.mode=develop,debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.infinity

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

CMD sleep infinity
