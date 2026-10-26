FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN a2enmod rewrite

COPY . /var/www/html/

RUN printf '<Directory /var/www/html/>\n    Options FollowSymLinks\n    AllowOverride All\n    Require all granted\n</Directory>\nDirectoryIndex index.php index.html\n' > /etc/apache2/conf-available/dukaandesk.conf 
&& a2enconf dukaandesk 
&& test -f /var/www/html/index.php

RUN chown -R www-data:www-data /var/www/html/

EXPOSE 80
