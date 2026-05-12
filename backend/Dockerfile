FROM php:8.2-apache

# Tooling di sistema
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libpq-dev \
        libssl-dev \
        pkg-config \
    && rm -rf /var/lib/apt/lists/*

# Estensione PostgreSQL
RUN docker-php-ext-install pdo_pgsql

# Estensione MongoDB
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# Apache: rewrite + .htaccess
RUN a2enmod rewrite \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Codice applicativo
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html

# Entrypoint: forza singolo MPM e sostituisce $PORT a runtime
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENV PORT=80
EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
