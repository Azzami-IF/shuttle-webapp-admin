#!/usr/bin/env bash
set -euo pipefail

# Usage: run on the target server in the project root as the deploy user
# Example:
#   ssh deploy@admin.ambatu.my.id
#   cd /var/www/admin.ambatu.my.id
#   sudo -u www-data bash deploy/prepare_deploy.sh

echo "Preparing Shuttle Admin for deploy..."

# 1) Copy production env template to .env if not present
if [ ! -f .env ]; then
  if [ -f .env.production ]; then
    cp .env.production .env
    echo "Created .env from .env.production; edit DB and mail creds now.";
  else
    echo ".env.production not found. Create .env manually.";
    exit 1
  fi
fi

# 2) Install PHP deps
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# 3) Generate app key if missing
php artisan key:generate --force

# 4) Run migrations
php artisan migrate --force

# 5) Create storage symlink
php artisan storage:link || true

# 6) Cache config, routes, views
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7) Build frontend assets (if applicable)
if [ -f package.json ]; then
  # use npm ci for reproducible builds
  npm ci --silent
  npm run build --silent
fi

echo "Deploy preparation complete. Ensure filesystem ownership and webserver configs are set." 
