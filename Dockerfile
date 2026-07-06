FROM php:8.2-apache

# Extensions PHP nécessaires à l'application (PDO MySQL, mysqli, curl, gd)
RUN apt-get update && apt-get install -y \
        libcurl4-openssl-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        unzip \
        git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mysqli curl gd \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Limites d'upload raisonnables (photos, dossiers médicaux, etc.)
RUN { \
        echo 'upload_max_filesize=20M'; \
        echo 'post_max_size=20M'; \
        echo 'memory_limit=256M'; \
    } > /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html

# Copie du code de l'application
COPY . /var/www/html/

# Permissions (dossiers d'upload écrits par l'app)
RUN mkdir -p /var/www/html/chatbot_kh/uploads \
    && chown -R www-data:www-data /var/www/html

# Render fournit le port d'écoute via la variable d'environnement PORT
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
