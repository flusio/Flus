FROM php:7.4-fpm

ENV COMPOSER_HOME /tmp

RUN apt-get update && \
    apt-get install -y git libpq-dev libzip-dev unzip libicu-dev locales && \
    pecl install xdebug && \
    docker-php-ext-configure intl && \
    docker-php-ext-install -j$(nproc) intl gettext zip pdo pdo_pgsql && \
    docker-php-ext-enable xdebug

RUN echo 'en_GB.UTF-8 UTF-8' >> /etc/locale.gen && \
    echo 'fr_FR.UTF-8 UTF-8' >> /etc/locale.gen && \
    locale-gen

COPY install-composer.sh .
RUN sh ./install-composer.sh && rm ./install-composer.sh
