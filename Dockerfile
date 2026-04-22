# 1. Base: PHP con Apache
FROM php:8.2-apache

# 2. Estensioni base
RUN docker-php-ext-install pdo pdo_mysql mysqli

# 3. Abilita rewrite
RUN a2enmod rewrite

# 4. Copia il tuo codice
COPY . /var/www/html/

# 5. Permessi
RUN chown -R www-data:www-data /var/www/html

# 6. Configurazione Porta (Semplice)
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# 7. COMANDO DI AVVIO (Il Fix per l'MPM)
# Prima di avviare Apache, disabilitiamo mpm_event che causa il conflitto
CMD ["/bin/bash", "-c", "a2dismod mpm_event && a2enmod mpm_prefork && apache2-foreground"]