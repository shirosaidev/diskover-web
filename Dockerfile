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
RUN cp /var/www/html/smartsearches.txt.sample /var/www/html/smartsearches.txt
RUN cp /var/www/html/customtags.txt.sample /var/www/html/customtags.txt
RUN cp /var/www/html/extrafields.txt.sample /var/www/html/extrafields.txt

ARG ES_HOST=elasticsearch

RUN sed -i "s!const ES_HOST = 'localhost';!const ES_HOST = '$ES_HOST';!g" /var/www/src/diskover/Constants.php
RUN ln -s /var/www/html/dashboard.php /var/www/html/index.php
