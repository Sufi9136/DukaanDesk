FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN a2enmod rewrite

COPY . /var/www/html/

RUN printf '%s\n' '<Directory /var/www/html/>' 'Options FollowSymLinks' 'AllowOverride All' 'Require all granted' '</Directory>' 'DirectoryIndex index.php index.html' > /etc/apache2/conf-available/dukaandesk.conf 
&& a2enconf dukaandesk 
&& test -f /var/www/html/index.php

RUN chown -R www-data:www-data /var/www/html/

EXPOSE 80
