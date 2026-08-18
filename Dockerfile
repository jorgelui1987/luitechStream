FROM php:8.2-apache

# Instalar extensiones de PHP necesarias
RUN docker-php-ext-install pdo pdo_mysql

# Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# Copiar archivos de la aplicación
COPY . /var/www/html/

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Exponer puerto 80
EXPOSE 80