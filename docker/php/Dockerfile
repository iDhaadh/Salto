# PHP 8.4 FPM with the Microsoft SQL Server driver (pdo_sqlsrv) so Laravel can
# read the SALTO Space database, plus the extensions Laravel needs.
FROM php:8.4-fpm-bookworm

# --- System packages ---------------------------------------------------------
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip curl ca-certificates gnupg2 apt-transport-https \
        libzip-dev libicu-dev libonig-dev unixodbc-dev \
    && rm -rf /var/lib/apt/lists/*

# --- Microsoft ODBC Driver 18 for SQL Server (Debian 12 / bookworm) ----------
# The prod.list ships a `signed-by=/usr/share/keyrings/microsoft-prod.gpg`
# directive, so the dearmored key must live at exactly that path.
RUN curl -fsSL https://packages.microsoft.com/keys/microsoft.asc \
        | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg \
    && curl -fsSL https://packages.microsoft.com/config/debian/12/prod.list \
        -o /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y --no-install-recommends msodbcsql18 \
    && rm -rf /var/lib/apt/lists/*

# --- PHP extensions ----------------------------------------------------------
RUN docker-php-ext-install -j"$(nproc)" pdo_mysql bcmath intl zip \
    && pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv

# --- Composer ----------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
