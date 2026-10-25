# ---- Stage 1: Build (Optional but recommended) ----
# Use this stage if you have Composer dependencies to install.
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader --prefer-dist

# ---- Stage 2: Runtime ----
FROM php:8.2-apache AS runtime

# Install PDO MySQL extensions and enable Apache modules
RUN docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite

# --- CRITICAL: Configure Apache to listen on port 10000 (Render's default) ---
# This modifies both the ports.conf and the default virtual host.
RUN sed -ri 's/^Listen 80$/Listen 10000/' /etc/apache2/ports.conf \
    && sed -ri 's/<VirtualHost \*:80>/<VirtualHost *:10000>/' /etc/apache2/sites-available/000-default.conf

# Pass environment variables from Render to Apache/PHP
# We still list them here so Apache knows to pass them to PHP.
# The actual VALUES are set securely in the Render Dashboard, not here.
RUN printf '%s\n' \
    'PassEnv PORT' \
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

# Set working directory
WORKDIR /var/www/html

# Copy application source code
COPY . /var/www/html/

# Copy Composer dependencies from the vendor stage (if used)
# COPY --from=vendor /app/vendor ./vendor

# --- Security: Run as a non-root user ---
# Create a non-privileged user and give it ownership of the web root.
# Note: You may need to adjust permissions depending on your app's write needs.
RUN chown -R www-data:www-data /var/www/html
USER www-data

# Expose the correct port
EXPOSE 10000