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

# Apache: rewrite + .htaccess
RUN a2enmod rewrite \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Forziamo un solo MPM (prefork). In alcune build i moduli MPM event/worker
# vengono caricati assieme a prefork e Apache rifiuta di avviarsi con:
#   "Configuration error: More than one MPM loaded."
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true \
    && a2enmod mpm_prefork

# Codice applicativo
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html

# Porta di default 80; Railway inietta $PORT a runtime.
ENV PORT=80
EXPOSE 80

# Sostituisce la porta in Apache solo quando il container parte
# (così la variabile $PORT è davvero risolta).
CMD ["sh", "-c", "\
    sed -i \"s/^Listen .*/Listen ${PORT}/\" /etc/apache2/ports.conf && \
    sed -i \"s|<VirtualHost \\*:[0-9]\\+>|<VirtualHost *:${PORT}>|\" /etc/apache2/sites-available/000-default.conf && \
    exec apache2-foreground \
"]
