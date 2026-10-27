# ---- Runtime ----
FROM php:8.2-apache AS runtime

# ------------------------------------------------------------------
# 1. System packages: PDO MySQL, Node.js 20 LTS
# ------------------------------------------------------------------
RUN apt-get update && apt-get install -y --no-install-recommends \
        curl \
        libcurl4-openssl-dev \
        ca-certificates \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

# ------------------------------------------------------------------
# 2. PHP extensions
# ------------------------------------------------------------------
RUN docker-php-ext-install curl pdo pdo_mysql

# ------------------------------------------------------------------
# 3. Apache modules
#    rewrite   — PHP routing (.htaccess)
#    proxy     — base proxy module
#    proxy_http — HTTP proxy (Socket.io long-polling)
#    proxy_wstunnel — WebSocket proxy (Socket.io WS upgrade)
# ------------------------------------------------------------------
RUN a2enmod rewrite proxy proxy_http proxy_wstunnel

# ------------------------------------------------------------------
# 4. Configure Apache to listen on port 10000 (Render's default)
# ------------------------------------------------------------------
RUN sed -ri 's/^Listen 80$/Listen 10000/' /etc/apache2/ports.conf \
    && sed -ri 's/<VirtualHost \*:80>/<VirtualHost *:10000>/' \
           /etc/apache2/sites-available/000-default.conf

# ------------------------------------------------------------------
# 5. Pass environment variables from Render to Apache/PHP
# ------------------------------------------------------------------
RUN printf '%s\n' \
    'PassEnv PORT' \
    'PassEnv RESEND_API_KEY' \
    'PassEnv MAIL_FROM' \
    'PassEnv RESEND_FROM' \
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
    'PassEnv GEMINI_API_KEY' \
    'PassEnv GEMINI_MODEL' \
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

# ------------------------------------------------------------------
# 6. Apache: proxy /socket.io/ → internal Node.js on :3000
#    (WebSocket upgrade + HTTP long-polling fallback)
# ------------------------------------------------------------------
COPY socketio-proxy.conf /etc/apache2/conf-available/socketio-proxy.conf
RUN a2enconf socketio-proxy

# ------------------------------------------------------------------
# 7. Copy application source
# ------------------------------------------------------------------
WORKDIR /var/www/html
COPY . /var/www/html/

# ------------------------------------------------------------------
# 8. Install Node.js production dependencies (for server.ts / Socket.io)
# ------------------------------------------------------------------
RUN npm install --omit=dev

# ------------------------------------------------------------------
# 9. Make the entrypoint executable and fix ownership
# ------------------------------------------------------------------
RUN chmod +x /var/www/html/docker-entrypoint.sh \
    && chown -R www-data:www-data /var/www/html

# ------------------------------------------------------------------
# 10. Run as non-root
# ------------------------------------------------------------------
USER www-data

EXPOSE 10000

# Starts Node.js on :3000 (background) then Apache on :10000 (foreground)
CMD ["/bin/bash", "/var/www/html/docker-entrypoint.sh"]
