FROM php:8.3-apache

RUN a2enmod headers

COPY security.conf /etc/apache2/conf-enabled/security.conf
COPY index.php /var/www/html/index.php

EXPOSE 80