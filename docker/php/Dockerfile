FROM php:8.2-fpm

ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    gnupg \
    ca-certificates \
    unixodbc-dev \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && curl https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg \
    && curl https://packages.microsoft.com/config/debian/12/prod.list > /etc/apt/sources.list.d/mssql-release.list \
    && sed -i 's#deb \[arch=amd64,arm64,armhf signed-by=/usr/share/keyrings/microsoft-prod.gpg\]#deb [arch=amd64 signed-by=/usr/share/keyrings/microsoft-prod.gpg]#' /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y msodbcsql18 \
    && docker-php-ext-install pdo mbstring bcmath gd zip opcache \
    && pecl install redis sqlsrv-5.12.0 pdo_sqlsrv-5.12.0 \
    && docker-php-ext-enable opcache redis sqlsrv pdo_sqlsrv \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/99-opcache.ini

RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/api

CMD ["php-fpm"]
