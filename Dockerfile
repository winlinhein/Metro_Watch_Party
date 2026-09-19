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
    'PassEnv TURN_URLS' \
    'PassEnv TURN_USERNAME' \
    'PassEnv TURN_CREDENTIAL' \
    'PassEnv TURN_SECRET' \
    'PassEnv TURN_HOST' \
    'PassEnv METERED_TURN_APP' \
    'PassEnv METERED_TURN_API_KEY' \
    'PassEnv STRIPE_SECRET_KEY' \
    'PassEnv STRIPE_PUBLISHABLE_KEY' \
    'PassEnv PREMIUM_PRICE_ID' \
    'PassEnv PUSHER_APP_ID' \
    'PassEnv PUSHER_KEY' \
    'PassEnv PUSHER_SECRET' \
    'PassEnv PUSHER_CLUSTER' \
    'PassEnv DB_HOST' \
    'PassEnv DB_PORT' \
    'PassEnv DB_NAME' \
    'PassEnv DB_USER' \
    'PassEnv DB_PASSWORD' \
    'PassEnv GOOGLE_CLIENT_ID' \
    'PassEnv GOOGLE_CLIENT_SECRET' \
    > /etc/apache2/conf-available/passenv.conf \
    && a2enconf passenv

# Copy all your project files into the Apache server directory
COPY . /var/www/html/

# Expose port 80 (Render automatically detects and routes this)
EXPOSE 80
