# Production deploy (EC2 + ecedula.com)

These files help run eCedula on a single Ubuntu EC2 instance behind Nginx.

## Files

| File | Purpose |
|------|---------|
| [nginx/ecedula.conf](nginx/ecedula.conf) | Nginx site: SPA + `/api` + `/sanctum` + `/storage` |
| [server-setup.sh](server-setup.sh) | First-time package install + clone + enable site |
| [deploy.sh](deploy.sh) | Pull, migrate, build frontend, reload services |
| [../backend/.env.production.example](../backend/.env.production.example) | Production Laravel env template |
| [../frontend/.env.production.example](../frontend/.env.production.example) | Keep `VITE_API_URL` empty for same-origin API |

## First-time setup

1. Open EC2 security group ports **22**, **80**, **443**.
2. Point DNS:
   - `ecedula.com` A → EC2 Elastic IP
   - `www.ecedula.com` A → same IP
3. SSH in and run:

```bash
curl -fsSL https://raw.githubusercontent.com/winstonpepito/cedula/main/deploy/server-setup.sh -o /tmp/server-setup.sh
# or after cloning:
bash /var/www/ecedula/deploy/server-setup.sh
```

4. Create MySQL database:

```bash
sudo mysql -e "CREATE DATABASE ecedula CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ecedula'@'localhost' IDENTIFIED BY 'strong-password';
GRANT ALL PRIVILEGES ON ecedula.* TO 'ecedula'@'localhost';
FLUSH PRIVILEGES;"
```

5. Configure Laravel:

```bash
cd /var/www/ecedula/backend
cp .env.production.example .env
nano .env   # set DB_* passwords, etc.
php artisan key:generate
```

6. Configure frontend build env (optional; defaults are fine):

```bash
cd /var/www/ecedula/frontend
cp .env.production.example .env.production
```

7. Deploy:

```bash
cd /var/www/ecedula
bash deploy/deploy.sh
```

8. HTTPS:

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d ecedula.com -d www.ecedula.com
```

9. Change seeded admin password after login (`admin@ecedula.local` / `password`).

## Later updates

```bash
cd /var/www/ecedula
bash deploy/deploy.sh
```

Useful flags:

```bash
SKIP_MIGRATE=1 bash deploy/deploy.sh
SKIP_FRONTEND=1 bash deploy/deploy.sh
PHP_VERSION=8.3 bash deploy/deploy.sh
```

## PHP socket note

If PHP-FPM is not `php8.2-fpm`, edit `deploy/nginx/ecedula.conf` and change:

```text
unix:/run/php/php8.2-fpm.sock
```

to your socket (e.g. `php8.3-fpm.sock`), then:

```bash
sudo cp deploy/nginx/ecedula.conf /etc/nginx/sites-available/ecedula
sudo nginx -t && sudo systemctl reload nginx
```
