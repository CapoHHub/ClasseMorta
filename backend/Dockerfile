FROM php:8.2-apache
RUN a2enmod rewrite
RUN docker-php-ext-install pdo_mysql
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html 
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf
EXPOSE ${PORT}