FROM php:8.2-apache

# Install PDO MySQL extensions required for your conn.php
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite just in case you need URL routing later
RUN a2enmod rewrite

# Copy all your project files into the Apache server directory
COPY . /var/www/html/

# Expose port 80 (Render automatically detects and routes this)
EXPOSE 80
