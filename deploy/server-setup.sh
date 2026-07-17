#!/usr/bin/env bash
# One-time server bootstrap helpers for Ubuntu 22.04/24.04 on EC2.
# Review before running. Does NOT create MySQL users or SSL certs for you.
#
# Usage:
#   bash deploy/server-setup.sh

set -euo pipefail

PHP_VERSION="${PHP_VERSION:-8.2}"
APP_DIR="${APP_DIR:-/var/www/ecedula}"
REPO_URL="${REPO_URL:-https://github.com/winstonpepito/cedula.git}"

log() { printf '\n==> %s\n' "$*"; }

log "Installing packages"
sudo apt-get update -y
sudo apt-get install -y \
  nginx mysql-server unzip git curl \
  "php${PHP_VERSION}-fpm" \
  "php${PHP_VERSION}-cli" \
  "php${PHP_VERSION}-mysql" \
  "php${PHP_VERSION}-xml" \
  "php${PHP_VERSION}-mbstring" \
  "php${PHP_VERSION}-curl" \
  "php${PHP_VERSION}-zip" \
  "php${PHP_VERSION}-gd" \
  "php${PHP_VERSION}-bcmath"

if ! command -v composer >/dev/null 2>&1; then
  log "Installing Composer"
  curl -sS https://getcomposer.org/installer | php
  sudo mv composer.phar /usr/local/bin/composer
fi

if ! command -v node >/dev/null 2>&1; then
  log "Installing Node.js 20"
  curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
  sudo apt-get install -y nodejs
fi

log "Preparing app directory $APP_DIR"
sudo mkdir -p "$APP_DIR"
sudo chown -R "$USER:www-data" "$APP_DIR"

if [[ ! -d "$APP_DIR/.git" ]]; then
  git clone "$REPO_URL" "$APP_DIR"
else
  echo "Repo already present at $APP_DIR"
fi

log "Installing Nginx site config"
sudo cp "$APP_DIR/deploy/nginx/ecedula.conf" /etc/nginx/sites-available/ecedula
sudo ln -sf /etc/nginx/sites-available/ecedula /etc/nginx/sites-enabled/ecedula
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl enable nginx "php${PHP_VERSION}-fpm"
sudo systemctl reload nginx
sudo systemctl restart "php${PHP_VERSION}-fpm"

cat <<EOF

Next steps:
  1. Create MySQL database/user for ecedula
  2. cp $APP_DIR/backend/.env.production.example $APP_DIR/backend/.env
     then edit DB_*, APP_KEY (php artisan key:generate), secrets
  3. Point DNS A records for ecedula.com (+ www) to this server
  4. bash $APP_DIR/deploy/deploy.sh
  5. sudo apt install -y certbot python3-certbot-nginx
     sudo certbot --nginx -d ecedula.com -d www.ecedula.com
  6. Change default admin password after first login

EOF
