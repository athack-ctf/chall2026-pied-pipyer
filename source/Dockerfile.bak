FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libzip-dev unzip git openssl libsqlite3-dev sqlite3 \
    python3 python3-pip python3-venv \
    && python3 -m pip install --ignore-installed --upgrade pip==24.1b1 --break-system-packages  \
    && docker-php-ext-install pdo pdo_sqlite zip \
    && apt-get clean

RUN a2enmod rewrite

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . /var/www/html

RUN composer install --no-dev --optimize-autoloader

RUN pip3 install --break-system-packages -r flask-app/requirements.txt

RUN cd /var/www/html/flask-app/local-package && \
    python3 setup.py bdist_wheel && \
    mkdir -p /app/local-packages && \
    cp dist/*.whl /app/local-packages/ && \
    cd /var/www/html

RUN mkdir -p data keys /app/installed-packages /tmp/pip-install-logs && \
    mkdir -p repo-data/packages repo-data/simple repo-data/manifest && \
    mkdir -p repo-data/tmp/uploads repo-data/tmp/imports && \
    chown -R www-data:www-data data repo-data keys /app /tmp/pip-install-logs && \
    chmod -R 755 data repo-data keys /app

RUN chmod +x /var/www/html/generate-keys.sh && \
    chown -R www-data:www-data keys

RUN chmod -R a+w /usr/local/lib/python3.13/dist-packages

RUN chmod +x /var/www/html/flask-app/start-flask.sh

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
