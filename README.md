# eCedula

Community Tax Certificate web app — React + Laravel + MySQL.

Applicants apply online, get a live tax computation, choose soft copy / pickup / barangay delivery, pay via PayMongo (card/GCash) or upload payment proof, then track the transaction with a QR receipt. Staff manage rates, delivery fees, reports, and delivery status.

## Stack

- **Frontend:** React 18, Vite, TypeScript, Tailwind CSS, React Router
- **Backend:** Laravel 12 API, Sanctum (staff SPA auth)
- **Database:** MySQL 8 / MariaDB
- **Payments:** PayMongo Checkout (mock checkout when keys are unset)
- **PDF/QR:** DomPDF + Simple QrCode

## Quick start

### 1. Database

Create a MySQL database named `ecedula` (XAMPP example):

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS ecedula CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Or use Docker Compose (optional):

```bash
docker compose up -d
```

Then set `DB_PASSWORD=secret`, `DB_USERNAME=ecedula` in `backend/.env` if using the compose MySQL service.

### 2. Backend

```bash
cd backend
cp .env.example .env   # if needed; project already has .env for local XAMPP
composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

API: `http://127.0.0.1:8000`

### 3. Frontend

```bash
cd frontend
npm install
npm run dev
```

App: `http://localhost:5173`  
Vite proxies `/api` and `/sanctum` to the Laravel server.

## Demo staff logins

| Role     | Email                   | Password |
|----------|-------------------------|----------|
| Admin    | `admin@ecedula.local`   | `password` |
| Delivery | `delivery@ecedula.local`| `password` |

Admins manage staff under **Admin → Staff users** (create/edit/delete delivery and admin accounts).  
Delivery users open **Applications**, download receipt PDFs (applicant name / detail page), and mark **Out for delivery** / **Delivered**.

Seed (users, tax defaults, landing copy, Cebu City barangays + delivery fees):

```bash
cd backend
php artisan db:seed
```

## PayMongo

Set in `backend/.env`:

```env
PAYMONGO_ENABLED=true
PAYMONGO_SECRET_KEY=sk_test_...
PAYMONGO_PUBLIC_KEY=pk_test_...
PAYMONGO_WEBHOOK_SECRET=whsec_...
PAYMONGO_PAYMENT_METHODS=qrph,card,gcash
```

Webhook URL: `POST /api/webhooks/paymongo`  
Event: `checkout_session.payment.paid`

Methods in `PAYMONGO_PAYMENT_METHODS` must also be **Active** under PayMongo Dashboard → Payment Methods. If checkout says “No payment methods are available”, the session is requesting inactive channels (e.g. only `card`/`gcash` while only QRPh is active).

When `PAYMONGO_ENABLED=false` or keys are empty, checkout uses a **mock payment page** so you can finish the flow locally.

## Tax rules (defaults)

Editable under **Admin → Tax settings**.

**Individual:** ₱5 base + ₱1 per ₱1,000 of annual income (monthly×12 + 13th + bonuses), additional capped at ₱5,000.

**Corporation:** ₱500 base + ₱1 per ₱5,000 of property and of gross receipts (matches spreadsheet sample), additional capped at ₱10,000.

**Late interest:** after Feb 28, 2% per month counted from January on the community tax total.

## Main routes

| Path | Purpose |
|------|---------|
| `/` | Landing |
| `/apply` | Application wizard |
| `/pay/:tracking` | PayMongo / proof upload |
| `/receipt/:tracking` | Receipt + QR + downloads |
| `/track`, `/t/:tracking` | Tracking |
| `/admin` | Staff console |

## Project layout

```
cedula/
  backend/     Laravel API
  frontend/    React SPA
  deploy/      Nginx config + EC2 deploy scripts
  docker-compose.yml
```

## Production (EC2 + ecedula.com)

See **[deploy/README.md](deploy/README.md)** for full steps.

Quick path on Ubuntu EC2:

```bash
# after DNS A records point to the server
bash deploy/server-setup.sh
cd /var/www/ecedula/backend
cp .env.production.example .env   # edit DB + secrets
php artisan key:generate
cd /var/www/ecedula && bash deploy/deploy.sh
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d ecedula.com -d www.ecedula.com
```

Later updates: `bash deploy/deploy.sh`
