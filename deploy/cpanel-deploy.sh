#!/bin/bash
# ============================================================
# Daily Niyamat - Automated cPanel Deployment Script
# ============================================================

set -e

echo "🚀 Starting Daily Niyamat setup on cPanel..."

# 1. Paths & Detection
USER_HOME="$HOME"
REPO_DIR="$USER_HOME/repositories/dailyniyamat"
PUBLIC_HTML="$USER_HOME/public_html"

if [ ! -d "$REPO_DIR" ]; then
    echo "❌ Error: $REPO_DIR not found!"
    exit 1
fi

cd "$REPO_DIR"

# 2. Detect PHP 8.2+
PHP_BIN="php"
if [ -x "/opt/cpanel/ea-php82/root/usr/bin/php" ]; then
    PHP_BIN="/opt/cpanel/ea-php82/root/usr/bin/php"
elif [ -x "/usr/local/bin/ea-php82" ]; then
    PHP_BIN="/usr/local/bin/ea-php82"
elif [ -x "/opt/cpanel/ea-php83/root/usr/bin/php" ]; then
    PHP_BIN="/opt/cpanel/ea-php83/root/usr/bin/php"
elif [ -x "/usr/local/bin/ea-php83" ]; then
    PHP_BIN="/usr/local/bin/ea-php83"
fi

echo "✓ Using PHP: $($PHP_BIN -v | head -n 1)"

# 3. Setup .env
if [ ! -f "$REPO_DIR/.env" ]; then
    echo "Creating .env configuration..."
    cat << 'EOF' > "$REPO_DIR/.env"
APP_NAME="Daily Niyamat"
APP_ENV=production
APP_KEY=base64:5N28+WmxmUCPos7oTo4eneqQzfT+/7gYVheB/ap3E3w=
APP_DEBUG=false
APP_TIMEZONE=Asia/Dhaka
APP_URL=https://dailyniyamat.com

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US
APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12
LOG_CHANNEL=single
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dailyniy_niyamat
DB_USERNAME=dailyniy_niyamat
DB_PASSWORD="raju@1919"

SESSION_DRIVER=file
SESSION_LIFETIME=120
CACHE_STORE=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=public
EOF
    echo "✓ .env file created."
else
    echo "✓ Existing .env file found."
fi

# 4. Storage Directories & Permissions
echo "Setting permissions..."
mkdir -p storage/framework/{views,cache/data,sessions} storage/logs storage/app/public bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 5. Composer Install
echo "Installing Composer dependencies..."
if [ ! -f "composer.phar" ]; then
    echo "Downloading composer.phar..."
    curl -sS -L https://getcomposer.org/download/latest-stable/composer.phar -o composer.phar || wget -q https://getcomposer.org/download/latest-stable/composer.phar -O composer.phar || true
fi

if [ -f "composer.phar" ]; then
    echo "Running composer.phar..."
    $PHP_BIN -d memory_limit=-1 composer.phar install --no-dev --optimize-autoloader --no-interaction
elif command -v composer &> /dev/null; then
    echo "Running system composer..."
    $PHP_BIN -d memory_limit=-1 $(which composer) install --no-dev --optimize-autoloader --no-interaction
else
    echo "❌ Error: Composer could not be executed."
    exit 1
fi

# 6. Database Migrations & Seeds
echo "Running database migrations..."
$PHP_BIN artisan migrate --force

echo "Seeding default data (catalog & admin)..."
$PHP_BIN artisan db:seed --force

# 7. Setup public_html
echo "Configuring public_html..."
mkdir -p "$PUBLIC_HTML"

# Copy build assets & static files
cp -r public/build "$PUBLIC_HTML/" 2>/dev/null || true
cp -r public/css "$PUBLIC_HTML/" 2>/dev/null || true
cp -r public/js "$PUBLIC_HTML/" 2>/dev/null || true
cp public/favicon.ico "$PUBLIC_HTML/" 2>/dev/null || true
cp public/robots.txt "$PUBLIC_HTML/" 2>/dev/null || true

# Setup .htaccess with ea-php82 handler
cat << 'HTEOF' > "$PUBLIC_HTML/.htaccess"
# php -- BEGIN cPanel-generated handler, do not edit
# Set the “ea-php82” package as the default “PHP” programming language.
<IfModule mime_module>
  AddHandler application/x-httpd-ea-php82 .php .php8 .phtml
  AddHandler application/x-httpd-ea-php82___lsphp .php .php8 .phtml
</IfModule>
# php -- END cPanel-generated handler, do not edit

HTEOF
cat public/.htaccess >> "$PUBLIC_HTML/.htaccess"

# Create bridge index.php in public_html
cat << 'INDEXEOF' > "$PUBLIC_HTML/index.php"
<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Point to Laravel project inside repositories/dailyniyamat
$projectPath = __DIR__ . '/../repositories/dailyniyamat';

if (file_exists($maintenance = $projectPath . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $projectPath . '/vendor/autoload.php';

(require_once $projectPath . '/bootstrap/app.php')
    ->handleRequest(Request::capture());
INDEXEOF

# Link storage
rm -rf "$PUBLIC_HTML/storage"
ln -s "$REPO_DIR/storage/app/public" "$PUBLIC_HTML/storage"
echo "✓ Storage linked: $PUBLIC_HTML/storage -> $REPO_DIR/storage/app/public"

# 8. Optimize Cache
echo "Optimizing cache..."
$PHP_BIN artisan optimize:clear
$PHP_BIN artisan optimize

echo ""
echo "============================================================"
echo "🎉 Daily Niyamat is now LIVE!"
echo "Admin Login:"
echo "Email: admin@dailyniyamat.com"
echo "Password: raju@1919"
echo "============================================================"
