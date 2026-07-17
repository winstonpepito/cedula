#!/usr/bin/env bash
# eCedula production deploy script
#
# Usage (on the EC2 server):
#   cd /var/www/ecedula
#   bash deploy/deploy.sh
#
# Optional env overrides:
#   APP_DIR=/var/www/ecedula PHP_VERSION=8.2 SKIP_MIGRATE=1 bash deploy/deploy.sh

set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/ecedula}"
PHP_VERSION="${PHP_VERSION:-8.2}"
WEB_USER="${WEB_USER:-www-data}"
BRANCH="${BRANCH:-main}"
SKIP_MIGRATE="${SKIP_MIGRATE:-0}"
SKIP_FRONTEND="${SKIP_FRONTEND:-0}"
SKIP_BACKEND="${SKIP_BACKEND:-0}"

log() { printf '\n==> %s\n' "$*"; }

if [[ ! -d "$APP_DIR/.git" ]]; then
  echo "ERROR: $APP_DIR is not a git checkout. Clone the repo there first."
  exit 1
fi

cd "$APP_DIR"

log "Pulling latest code ($BRANCH)"
git fetch origin
git checkout "$BRANCH"
git pull --ff-only origin "$BRANCH"

if [[ "$SKIP_BACKEND" != "1" ]]; then
  log "Backend: Composer install"
  cd "$APP_DIR/backend"

  if [[ ! -f .env ]]; then
    echo "ERROR: backend/.env is missing. Copy backend/.env.production.example to backend/.env and fill it in."
    exit 1
  fi

  composer install --no-dev --optimize-autoloader --no-interaction

  if [[ "$SKIP_MIGRATE" != "1" ]]; then
    log "Backend: migrate"
    php artisan migrate --force
  else
    log "Backend: skipping migrate (SKIP_MIGRATE=1)"
  fi

  log "Backend: storage link + caches"
  php artisan storage:link || true
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache

  log "Backend: permissions"
  sudo chown -R "$WEB_USER:$WEB_USER" storage bootstrap/cache
  sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
  sudo find storage bootstrap/cache -type f -exec chmod 664 {} \;
fi

if [[ "$SKIP_FRONTEND" != "1" ]]; then
  log "Frontend: npm install + production build"
  cd "$APP_DIR/frontend"

  # Empty VITE_API_URL => browser calls same-origin /api and /sanctum
  if [[ -f .env.production ]]; then
    # shellcheck disable=SC1091
    set -a
    # Prefer explicit production env for the build if present
    # (do not fail if file only has comments)
    source .env.production || true
    set +a
  fi
  export VITE_API_URL="${VITE_API_URL:-}"

  # Prefer clean install; fall back if lockfile is slightly out of sync across npm versions/platforms
  if ! npm ci; then
    log "npm ci failed — falling back to npm install"
    rm -rf node_modules
    npm install
  fi
  npm run build

  if [[ ! -f dist/index.html ]]; then
    echo "ERROR: frontend/dist/index.html missing after build"
    exit 1
  fi
fi

log "Reload PHP-FPM + Nginx"
if systemctl list-units --type=service --all | grep -q "php${PHP_VERSION}-fpm"; then
  sudo systemctl reload "php${PHP_VERSION}-fpm"
elif systemctl list-units --type=service --all | grep -q "php-fpm"; then
  sudo systemctl reload php-fpm
else
  echo "WARN: could not find php-fpm service to reload"
fi

sudo nginx -t
sudo systemctl reload nginx

log "Deploy complete"
echo "App dir:  $APP_DIR"
echo "Site:     check https://ecedula.com"
echo "Health:   curl -sS https://ecedula.com/up"
