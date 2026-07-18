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
BRANCH="${BRANCH:-main}"
SKIP_MIGRATE="${SKIP_MIGRATE:-0}"
SKIP_FRONTEND="${SKIP_FRONTEND:-0}"
SKIP_BACKEND="${SKIP_BACKEND:-0}"
DEPLOY_USER="$(id -un)"
DEPLOY_GROUP="$(id -gn)"

# Auto-detect Nginx user (for static files) and PHP-FPM pool user (for uploads).
# Amazon Linux often runs Nginx as nginx but PHP-FPM as apache — mismatch breaks storage writes.
detect_php_fpm_user() {
  local conf user
  for conf in \
    /etc/php-fpm.d/www.conf \
    /etc/php/*/fpm/pool.d/www.conf \
    /etc/php-fpm.conf
  do
    if [[ -f "$conf" ]]; then
      user="$(sudo grep -E '^\s*user\s*=' "$conf" | head -1 | awk -F= '{print $2}' | tr -d '[:space:]' || true)"
      if [[ -n "$user" ]]; then
        echo "$user"
        return 0
      fi
    fi
  done
  return 1
}

if [[ -z "${WEB_USER:-}" ]]; then
  if id nginx &>/dev/null; then
    WEB_USER=nginx
  elif id www-data &>/dev/null; then
    WEB_USER=www-data
  else
    WEB_USER=www-data
  fi
fi

if [[ -z "${PHP_USER:-}" ]]; then
  PHP_USER="$(detect_php_fpm_user || true)"
  if [[ -z "$PHP_USER" ]]; then
    if id apache &>/dev/null; then
      PHP_USER=apache
    else
      PHP_USER="$WEB_USER"
    fi
  fi
fi

log() { printf '\n==> %s\n' "$*"; }

if [[ ! -d "$APP_DIR/.git" ]]; then
  echo "ERROR: $APP_DIR is not a git checkout. Clone the repo there first."
  exit 1
fi

cd "$APP_DIR"

# storage/bootstrap/cache were often chowned to the web user; git then cannot
# update tracked .gitignore files inside them. Hand the tree back to the deploy user first.
log "Fixing ownership for git update (as $DEPLOY_USER; nginx=$WEB_USER php-fpm=$PHP_USER)"
sudo chown -R "$DEPLOY_USER:$DEPLOY_GROUP" \
  "$APP_DIR/backend/bootstrap/cache" \
  "$APP_DIR/backend/storage" \
  "$APP_DIR/.git" 2>/dev/null || true
# Also reclaim the rest of the checkout if a prior root/sudo left files unwritable
sudo chown -R "$DEPLOY_USER:$DEPLOY_GROUP" "$APP_DIR"

log "Pulling latest code ($BRANCH)"
git fetch origin
git checkout "$BRANCH"

# Production servers should match origin. Local edits to tracked files (e.g. package-lock
# from a prior npm install) must not block deploys. Preserve env files.
if ! git diff --quiet || ! git diff --cached --quiet; then
  log "Discarding local changes to tracked files (keeping .env*)"
  git stash push -m "deploy.sh auto-stash $(date -u +%Y%m%dT%H%M%SZ)" -- || true
fi

git reset --hard "origin/${BRANCH}"
# Remove untracked build junk, but never delete env files
git clean -fd -e 'backend/.env' -e 'frontend/.env' -e 'frontend/.env.production' -e 'frontend/dist'

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

  log "Backend: permissions (owner=$DEPLOY_USER group=$PHP_USER for PHP-FPM writes)"
  # Deploy user owns files; PHP-FPM pool user is the group so uploads work.
  # On Amazon Linux this is often "apache", not "nginx".
  mkdir -p storage/app/public/landing storage/app/private storage/framework/{cache,sessions,views} storage/logs
  sudo chown -R "$DEPLOY_USER:$PHP_USER" storage bootstrap/cache
  sudo find storage bootstrap/cache -type d -exec chmod 2775 {} \;
  sudo find storage bootstrap/cache -type f -exec chmod 664 {} \;
  # ACL so both nginx (serving /storage) and php-fpm can read/write if they differ
  if command -v setfacl >/dev/null 2>&1; then
    sudo setfacl -R -m "u:${PHP_USER}:rwx" -m "d:u:${PHP_USER}:rwx" storage bootstrap/cache || true
    if [[ "$WEB_USER" != "$PHP_USER" ]]; then
      sudo setfacl -R -m "u:${WEB_USER}:rx" -m "d:u:${WEB_USER}:rx" storage/app/public || true
    fi
  fi
  # Quick write probe as the PHP-FPM user
  if sudo -u "$PHP_USER" test -w storage/app/public; then
    log "PHP-FPM user $PHP_USER can write storage/app/public"
  else
    echo "WARN: $PHP_USER still cannot write storage/app/public — uploads will fail"
    ls -la storage/app/public || true
  fi
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

log "Ensure Nginx PHP-FPM socket matches this host"
PHP_SOCK=""
for candidate in \
  "/run/php-fpm/www.sock" \
  "/run/php/php${PHP_VERSION}-fpm.sock" \
  "/run/php/php-fpm.sock" \
  "/var/run/php-fpm/www.sock" \
  "/var/run/php/php${PHP_VERSION}-fpm.sock"
do
  if [[ -S "$candidate" ]]; then
    PHP_SOCK="$candidate"
    break
  fi
done
if [[ -z "$PHP_SOCK" ]]; then
  PHP_SOCK="$(
    sudo find /run /var/run -name '*.sock' 2>/dev/null \
      | grep -E 'php|fpm' \
      | head -1 || true
  )"
fi
NGINX_SITE=""
for conf in \
  /etc/nginx/conf.d/ecedula.conf \
  /etc/nginx/sites-enabled/ecedula \
  /etc/nginx/sites-available/ecedula
do
  if [[ -f "$conf" ]]; then
    NGINX_SITE="$conf"
    break
  fi
done
if [[ -n "$PHP_SOCK" && -n "$NGINX_SITE" ]]; then
  log "Patching $NGINX_SITE → fastcgi_pass unix:${PHP_SOCK}"
  sudo sed -i -E "s|fastcgi_pass unix:[^;]+;|fastcgi_pass unix:${PHP_SOCK};|g" "$NGINX_SITE"
elif [[ -z "$PHP_SOCK" ]]; then
  echo "WARN: no PHP-FPM socket found — API will 502 until Nginx fastcgi_pass is fixed"
  echo "      ls -la /run/php-fpm/*.sock /run/php/*.sock"
fi

log "Reload PHP-FPM + Nginx"
# Amazon Linux: php-fpm.service | Ubuntu: php8.2-fpm.service | Remi: php82-php-fpm.service
PHP_FPM_SERVICE=""
for candidate in \
  "php-fpm" \
  "php${PHP_VERSION}-fpm" \
  "php${PHP_VERSION//./}-php-fpm"
do
  if systemctl list-unit-files "${candidate}.service" --no-legend 2>/dev/null \
    | awk '{print $1}' | grep -qx "${candidate}.service"
  then
    PHP_FPM_SERVICE="$candidate"
    break
  fi
done
if [[ -z "$PHP_FPM_SERVICE" ]]; then
  PHP_FPM_SERVICE="$(
    systemctl list-unit-files --type=service --no-legend 2>/dev/null \
      | awk '{print $1}' \
      | grep -E 'fpm\.service$' \
      | grep -i php \
      | head -1 \
      | sed 's/\.service$//' || true
  )"
fi
if [[ -n "$PHP_FPM_SERVICE" ]]; then
  log "Reloading ${PHP_FPM_SERVICE}"
  sudo systemctl reload "$PHP_FPM_SERVICE" || sudo systemctl restart "$PHP_FPM_SERVICE"
else
  echo "WARN: could not find php-fpm service to reload"
  echo "      Run: systemctl list-unit-files | grep -i fpm"
fi

sudo nginx -t
sudo systemctl reload nginx

log "Deploy complete"
echo "App dir:  $APP_DIR"
echo "Site:     check https://ecedula.com"
echo "Health:   curl -sS https://ecedula.com/up"
