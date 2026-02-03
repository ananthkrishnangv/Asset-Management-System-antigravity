FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libicu-dev \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    gd \
    mysqli \
    pdo_mysql \
    zip \
    intl

# Enable Apache modules
RUN a2enmod rewrite ssl headers

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Copy Apache configuration
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Setup permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && mkdir -p /var/www/html/uploads /var/www/html/backups /var/www/html/logs \
    && chown -R www-data:www-data /var/www/html/uploads /var/www/html/backups /var/www/html/logs \
    && chmod -R 775 /var/www/html/uploads /var/www/html/backups /var/www/html/logs

# Fix SSL Key permissions if needed (though copying should preserve, safe to set)
# Assuming 'SSL Key' folder exists in source
RUN chmod 600 "/var/www/html/SSL Key/cert.key" || true

EXPOSE 80 443
