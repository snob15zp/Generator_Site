#FROM phpdockerio/php73-fpm:latest
FROM php:7.3-fpm
WORKDIR "/application"

# Fix debconf warnings upon build
ARG DEBIAN_FRONTEND=noninteractive

# Install selected extensions and other stuff
RUN apt-get update \
    && apt-get -y --no-install-recommends install  php-mysql \
    && apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

# Install git
RUN apt-get update \
    && apt-get -y install git \
    && apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/3


RUN apt-get update \
    && apt install -y libgmp-dev \
    && docker-php-ext-install gmp

RUN docker-php-ext-install mysqli pdo pdo_mysql