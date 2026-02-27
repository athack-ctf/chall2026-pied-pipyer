# --- STAGE 1: Builder ---
FROM php:8.2-apache AS builder

# 2. Install setuptools and wheel so setup.py can run
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev unzip git libsqlite3-dev python3 python3-pip python3-venv \
    && docker-php-ext-install zip pdo pdo_sqlite \
    && python3 -m pip install --ignore-installed --upgrade pip==24.1b1 --break-system-packages setuptools wheel  \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /build
COPY . .

RUN composer install --no-dev --optimize-autoloader

# Build the local Python package
RUN cd flask-app/local-package && \
    python3 setup.py bdist_wheel && \
    mkdir -p /build/dist-wheels && \
    cp dist/*.whl /build/dist-wheels/

# --- STAGE 2: Runtime ---
FROM php:8.2-apache

# Install dependencies needed for PHP extension compilation AND runtime
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    libsqlite3-dev \
    python3 \
    python3-pip \
    && python3 -m pip install --ignore-installed --upgrade pip==24.1b1 --break-system-packages \
    && docker-php-ext-install pdo pdo_sqlite zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy only necessary artifacts from builder
COPY --from=builder /build .
COPY --from=builder /build/dist-wheels /app/local-packages


# Combine multiple RUN commands to reduce layers
RUN pip3 install --no-cache-dir --break-system-packages -r flask-app/requirements.txt \
    && a2enmod rewrite \
    && mkdir -p data keys /app/installed-packages /tmp/pip-install-logs \
       repo-data/packages repo-data/simple repo-data/manifest \
       repo-data/tmp/uploads repo-data/tmp/imports \
    && chown -R www-data:www-data /var/www/html /app /tmp/pip-install-logs \
    && chmod -R 755 data repo-data keys /app \
    && chmod +x generate-keys.sh flask-app/start-flask.sh \
    && chmod -R a+w /usr/local/lib/python3.13/dist-packages


RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

RUN echo '<Directory /var/www/html/public>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/pypi.conf && a2enconf pypi

RUN echo 'RewriteEngine On\n\
RewriteCond %{REQUEST_FILENAME} !-f\n\
RewriteCond %{REQUEST_FILENAME} !-d\n\
RewriteRule ^(.*)$ index.php [QSA,L]' > /var/www/html/public/.htaccess

RUN echo '#!/bin/bash\n\
set -e\n\
\n\
/var/www/html/generate-keys.sh \n\
# Start Flask in background\n\
/var/www/html/flask-app/start-flask.sh &\n\
\n\
# Start Apache in foreground\n\
apache2-foreground\n\
' > /usr/local/bin/start-services.sh && chmod +x /usr/local/bin/start-services.sh

RUN chown -R www-data:www-data /var/www/html \
    && chown -R www-data:www-data /app \
    && chown -R www-data:www-data /tmp/pip-install-logs

EXPOSE 80

CMD ["/usr/local/bin/start-services.sh"]
