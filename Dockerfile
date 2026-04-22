# 1. Base: PHP 8.2 con Apache
FROM php:8.2-apache

# 2. Estensioni base (non toccano i moduli MPM)
RUN docker-php-ext-install pdo pdo_mysql mysqli

# 3. Abilita rewrite
RUN a2enmod rewrite

# 4. Copia il tuo codice
COPY . /var/www/html/

# 5. Permessi
RUN chown -R www-data:www-data /var/www/html

# 6. FIX PORTA (Versione più sicura)
# Invece di modificare i file interni, diciamo ad Apache di ascoltare 
# sulla porta che Railway ci passa tramite la variabile d'ambiente.
RUN echo "Listen \${PORT}" > /etc/apache2/ports.conf
RUN sed -i 's/<VirtualHost \*:80>/<VirtualHost \*:\${PORT}>/g' /etc/apache2/sites-available/000-default.conf

# 7. Start
CMD ["apache2-foreground"]