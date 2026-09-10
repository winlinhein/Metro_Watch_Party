FROM php:8.2-apache

# Install PDO MySQL extensions required for your conn.php
RUN docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite

# Let PHP see Render env vars (RESEND_API_KEY, MAIL_FROM, etc.)
RUN printf '%s\n' \
    'PassEnv RESEND_API_KEY' \
    'PassEnv MAIL_FROM' \
    'PassEnv MAIL_FROM_NAME' \
    'PassEnv CONTACT_TO' \
    'PassEnv SMTP_USER' \
    'PassEnv SMTP_PASS' \
    'PassEnv SMTP_HOST' \
    'PassEnv SMTP_PORT' \
    'PassEnv RENDER' \
    'PassEnv RENDER_SERVICE_ID' \
    > /etc/apache2/conf-available/passenv.conf \
    && a2enconf passenv

# Copy all your project files into the Apache server directory
COPY . /var/www/html/

# Expose port 80 (Render automatically detects and routes this)
EXPOSE 80
