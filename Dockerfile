FROM php:5.6-apache

# Habilita mod_rewrite para Apache
RUN a2enmod rewrite

# Aumenta el límite de memoria de PHP
RUN echo "memory_limit = 1024M" >> /usr/local/etc/php/conf.d/memory-limit.ini
# Aumenta el límite de tiempo de ejecución de PHP
RUN echo "max_execution_time = 3000" >> /usr/local/etc/php/conf.d/max-execution-time.ini

# Cambia los repositorios de APT a usar el archivo histórico y deshabilita la verificación de fechas de los paquetes
RUN sed -i '/deb.debian.org\/debian stretch-updates/d' /etc/apt/sources.list \
    && sed -i 's/deb.debian.org/archive.debian.org/g' /etc/apt/sources.list \
    && sed -i 's/security.debian.org/archive.debian.org/g' /etc/apt/sources.list \
    && apt-get -o Acquire::Check-Valid-Until=false update

# Instala las dependencias necesarias para las extensiones de PHP, incluyendo GD
RUN apt-get install --allow-unauthenticated -y libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install gd mysqli pdo pdo_mysql

# Copiar el archivo cacert.pem al contenedor
COPY ./cacert.pem /etc/ssl/certs/cacert.pem

# Configurar PHP para usar el archivo cacert.pem para cURL
RUN echo "curl.cainfo = /etc/ssl/certs/cacert.pem" >> /usr/local/etc/php/conf.d/cacert.ini

# Copia tu aplicación al contenedor
COPY . /var/www/html/
