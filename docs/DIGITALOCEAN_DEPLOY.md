# Deploy to DigitalOcean — Manual (no Docker)

Install the stack directly on an Ubuntu 24.04 Droplet. Run as `root` (or with `sudo`).

App needs: **PHP 8.4**, **MySQL**, **Redis** (Horizon), **nginx**, **Node 22**, **Supervisor** (Horizon), **cron** (scheduler).

> **No domain yet? Deploy by IP.** You can run the whole stack against the raw Droplet IP (e.g. `http://159.89.89.224`) for testing — every step below has an **IP-only** variant called out inline. Two things **do not work without a domain + HTTPS**: **payments** (Stripe/PayPal require `https`) and the **live-notification bell** (Reverb behind TLS). Add a domain + certbot (steps 12–13) later and both light up. For IP-only, use `http://DROPLET_IP` wherever a step says `your-domain.com`, and **skip steps 12–13**.

---

## 1. Droplet + DNS
- Create Droplet: **Ubuntu 24.04 LTS**, **2 GB RAM min**, SSH key.
- DNS **A record**: `your-domain.com → DROPLET_IP`. *(IP-only: skip DNS entirely.)*
- Connect: `ssh root@DROPLET_IP`

## 2. Firewall
```bash
ufw allow OpenSSH && ufw allow 'Nginx Full' && ufw enable
```

## 3. PHP 8.4 + extensions
```bash
apt update && apt install -y software-properties-common
add-apt-repository ppa:ondrej/php -y && apt update
apt install -y php8.4-fpm php8.4-cli php8.4-mysql php8.4-redis php8.4-gd \
  php8.4-exif php8.4-intl php8.4-bcmath php8.4-zip php8.4-mbstring \
  php8.4-xml php8.4-curl php8.4-opcache
```

## 4. Composer + Node 22
```bash
curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer
curl -fsSL https://deb.nodesource.com/setup_22.x | bash - && apt install -y nodejs
```

## 5. MySQL — create database
```bash
apt install -y mysql-server
mysql -u root <<'SQL'
CREATE DATABASE blackdesck CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'blackdesck'@'localhost' IDENTIFIED BY 'CHANGE_ME_DB_PASSWORD';
GRANT ALL PRIVILEGES ON blackdesck.* TO 'blackdesck'@'localhost';
FLUSH PRIVILEGES;
SQL
```

## 6. Redis + nginx + Supervisor
```bash
apt install -y redis-server nginx supervisor
systemctl enable --now redis-server
```

## 7. Get the code + build
```bash
cd /var/www
git clone https://github.com/<your-org>/blackdesck.git
cd blackdesck
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build
```

## 8. Configure `.env`
```bash
cp .env.example .env
php artisan key:generate
nano .env
```
Set at least:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com     # IP-only: http://DROPLET_IP

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=blackdesck
DB_USERNAME=blackdesck
DB_PASSWORD=CHANGE_ME_DB_PASSWORD

CACHE_STORE=redis
QUEUE_CONNECTION=redis          # MUST be redis — Horizon only processes Redis queues (see ⚠️ below)
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1

FILESYSTEM_DISK=s3          # DigitalOcean Spaces (set AWS_* keys); or 'local'
MAIL_MAILER=smtp            # set MAIL_* from your email provider

PAYPAL_MODE=live            # set STRIPE_* and PAYPAL_* live credentials (incl. STRIPE_WEBHOOK_SECRET + PAYPAL_WEBHOOK_ID — step 13)

# Real-time bell (Reverb behind nginx TLS — steps 10–11)
REVERB_APP_ID=blackdesck
REVERB_APP_KEY=CHANGE_ME_RANDOM
REVERB_APP_SECRET=CHANGE_ME_RANDOM
REVERB_HOST=your-domain.com         # IP-only: DROPLET_IP
REVERB_PORT=443                     # IP-only: 80
REVERB_SCHEME=https                 # IP-only: http (bell won't work without TLS — expected)

AI_PROVIDER=groq            # groq | gemini | openai — set the matching *_API_KEY (leave empty to disable AI features)
```
> ⚠️ `VITE_REVERB_*` values are **baked into the JS at build time** — after editing them,
> re-run `npm run build` (step 7 built before `.env` existed).
>
> ⚠️ **`QUEUE_CONNECTION`, `CACHE_STORE` and `SESSION_DRIVER` must all be `redis`, not the
> `database` defaults from `.env.example`.** Horizon (step 11) **only** processes Redis
> queues — if the queue is left on `database`, queued jobs land in the `jobs` table where
> Horizon never looks, and they silently never run (e.g. Stripe fee/net backfill,
> lead-scoring, digests). Verify with the check in step 9.

## 9. Migrate, seed, cache, permissions
```bash
php artisan migrate --force
php artisan db:seed --force          # roles, admin, countries, currencies, plans, experts
php artisan storage:link
php artisan config:cache
php artisan view:cache
php artisan event:cache
# NOTE: do NOT run route:cache — routes use closures and can't be serialized.

chown -R www-data:www-data /var/www/blackdesck
chmod -R 775 storage bootstrap/cache
```
**Verify the queue is on Redis** (else Horizon runs but processes nothing — see step 8 ⚠️):
```bash
php artisan tinker --execute="echo config('queue.default').PHP_EOL;"   # must print: redis
```
If it prints `database`, fix `QUEUE_CONNECTION=redis` in `.env`, re-run `php artisan config:cache`, and (after step 11) `php artisan horizon:terminate` to restart Horizon.

## 10. nginx site
```bash
cat > /etc/nginx/sites-available/blackdesck <<'NGINX'
server {
    listen 80;
    server_name your-domain.com;   # IP-only: server_name 159.89.89.224;  (or _ )
    root /var/www/blackdesck/public;
    index index.php;
    charset utf-8;
    client_max_body_size 25M;

    location / { try_files $uri $uri/ /index.php?$query_string; }

    # Reverb WebSocket (live notification bell)
    location /app {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    location ~ /\.(?!well-known).* { deny all; }
}
NGINX
ln -sf /etc/nginx/sites-available/blackdesck /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx
```

## 11. Horizon (Supervisor) + scheduler (cron)
```bash
cat > /etc/supervisor/conf.d/horizon.conf <<'CONF'
[program:horizon]
process_name=%(program_name)s
command=php /var/www/blackdesck/artisan horizon
user=www-data
autostart=true
autorestart=true
stopwaitsecs=3600
redirect_stderr=true
stdout_logfile=/var/www/blackdesck/storage/logs/horizon.log
CONF

cat > /etc/supervisor/conf.d/reverb.conf <<'CONF'
[program:reverb]
process_name=%(program_name)s
command=php /var/www/blackdesck/artisan reverb:start --host=127.0.0.1 --port=8080
user=www-data
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/www/blackdesck/storage/logs/reverb.log
CONF

supervisorctl reread && supervisorctl update && supervisorctl start horizon reverb

# Scheduler — run every minute
( crontab -l 2>/dev/null; echo "* * * * * cd /var/www/blackdesck && php artisan schedule:run >> /dev/null 2>&1" ) | crontab -
```

## 12. HTTPS (required for payments) — *needs a domain; skip if IP-only*
> IP-only deploys stop here: the site is live at `http://DROPLET_IP`. Payments and the live bell stay off until you add a domain and complete steps 12–13.
```bash
apt install -y certbot python3-certbot-nginx
certbot --nginx -d your-domain.com
```
Then in `.env` set `SESSION_SECURE_COOKIE=true` and `php artisan config:cache`.

## 13. Payment webhooks
- **PayPal:** set the webhook URL to `https://your-domain.com/paypal/webhook` (events: `PAYMENT.CAPTURE.COMPLETED/REFUNDED/DENIED`, `CUSTOMER.DISPUTE.CREATED`), put the **Webhook ID** in `.env` (`PAYPAL_WEBHOOK_ID`).
- **Stripe:** add an endpoint `https://your-domain.com/stripe/webhook` for `checkout.session.completed` (the server-side backstop when the buyer's browser never returns), put its **signing secret** in `.env` (`STRIPE_WEBHOOK_SECRET`).
- Then `php artisan config:cache`.

---

## Deploy a new version later
```bash
cd /var/www/blackdesck
git pull
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan view:cache && php artisan event:cache
php artisan horizon:terminate          # Supervisor restarts it with new code
chown -R www-data:www-data storage bootstrap/cache
```

## Backup the database
```bash
mysqldump -u blackdesck -p blackdesck > backup-$(date +%F).sql
```

## Still need (outside the server)
HTTPS (step 12) · SMTP account · Spaces (S3) keys · live payment keys.
