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

# Estensione MongoDB (driver ufficiale per PHP)
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# Apache: abilita rewrite e .htaccess
RUN a2enmod rewrite \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Codice applicativo
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html

# Permette di cambiare la porta di Apache via env
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf
EXPOSE ${PORT}
