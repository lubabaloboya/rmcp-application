#!/usr/bin/env sh
set -eu

cd /var/www/api

if [ ! -f .env ] && [ -f .env.example ]; then
    cp .env.example .env
fi

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

# Ensure Laravel runtime directories are writable for php-fpm workers.
mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

# Wait for SQL Server and create the application database when needed.
php <<'PHP'
<?php
$host = getenv('DB_HOST') ?: 'mssql';
$port = getenv('DB_PORT') ?: '1433';
$user = getenv('DB_USERNAME') ?: 'sa';
$pass = getenv('DB_PASSWORD') ?: '';
$dbName = getenv('DB_DATABASE') ?: 'rmcp';

$stdout = fopen('php://stdout', 'w');
$stderr = fopen('php://stderr', 'w');

$createSql = "IF DB_ID(N'".str_replace("'", "''", $dbName)."') IS NULL CREATE DATABASE [".str_replace(']', ']]', $dbName)."]";

while (true) {
    try {
        $pdo = new PDO(
            "sqlsrv:Server={$host},{$port};Database=master;Encrypt=Yes;TrustServerCertificate=Yes",
            $user,
            $pass
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec($createSql);
        fwrite($stdout, "Database is ready.\n");
        break;
    } catch (Throwable $e) {
        fwrite($stderr, "Waiting for SQL Server... {$e->getMessage()}\n");
        sleep(3);
    }
}
PHP

if grep -q '^APP_KEY=$' .env; then
    php artisan key:generate --force
fi

if grep -q '^JWT_SECRET=$' .env; then
    php artisan jwt:secret --force
fi

php artisan migrate --force
php artisan db:seed --force

# Warm Laravel caches so every request doesn't re-parse config/routes/views
php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan view:cache

php artisan schedule:work >/dev/stdout 2>/dev/stderr &

exec php-fpm -F
