FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN a2enmod rewrite

COPY . /var/www/html/

RUN echo "=== CHECKING COPIED PROJECT FILES ===" 
&& ls -la /var/www/html/ 
&& test -f /var/www/html/index.php 
&& echo "SUCCESS: index.php exists"

RUN chown -R www-data:www-data /var/www/html/

EXPOSE 80
