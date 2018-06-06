FROM php:7.0-apache

# Install composer

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Necessary to run composer
RUN apt-get update
RUN apt-get install -y git 

COPY composer.json /var/www
WORKDIR /var/www
RUN composer install

COPY public/ /var/www/html/
COPY src /var/www/src

RUN cp /var/www/src/diskover/Constants.php.sample /var/www/src/diskover/Constants.php
ARG ES_HOST=elasticsearch

RUN sed -i "s!const ES_HOST = 'localhost';!const ES_HOST = '$ES_HOST';!g" /var/www/src/diskover/Constants.php
RUN ln -s /var/www/html/selectindices.php /var/www/html/index.php
