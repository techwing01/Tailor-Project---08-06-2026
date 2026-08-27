FROM php:8.1-apache

RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    mariadb-client \
    && docker-php-ext-install pdo pdo_mysql mysqli mbstring xml \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite
COPY docker/servername.conf /etc/apache2/conf-available/servername.conf
RUN a2enconf servername

ENV APACHE_DOCUMENT_ROOT /var/www/html

COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80