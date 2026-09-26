Use this single script on a fresh Ubuntu 22.04/24.04 VPS.

Copy to deploy.sh
Edit the variables at the top (DOMAIN, APP_DIR, REPO_URL, DB creds, etc.)
Run with sudo bash deploy.sh
Bash

#!/usr/bin/env bash
set -euo pipefail

#############################
# EDIT THESE VARIABLES FIRST
#############################
DOMAIN="schoolportal.example.com"
WWW_DOMAIN="www.schoolportal.example.com"
APP_DIR="/var/www/vigu"
REPO_URL="https://github.com/your-org/your-repo.git"
BRANCH="main"

APP_ENV="production"
APP_NAME="Vigu"

DB_NAME="vigu_db"
DB_USER="vigu_user"
DB_PASS="ChangeThisStrongPassword123!"

# Super admin seeder class (leave empty to skip)
SUPERADMIN_SEEDER_CLASS="SuperAdminUserSeeder"

LETSENCRYPT_EMAIL="admin@example.com"

# PHP version
PHP_VERSION="8.3"

#############################
# SYSTEM PREP
#############################
export DEBIAN_FRONTEND=noninteractive

apt update -y
apt upgrade -y

apt install -y software-properties-common ca-certificates apt-transport-https lsb-release gnupg curl unzip git nginx mysql-server redis-server certbot python3-certbot-nginx

# PHP (Ondrej PPA)
add-apt-repository ppa:ondrej/php -y
apt update -y
apt install -y \
  "php${PHP_VERSION}-fpm" \
  "php${PHP_VERSION}-cli" \
  "php${PHP_VERSION}-mysql" \
  "php${PHP_VERSION}-mbstring" \
  "php${PHP_VERSION}-xml" \
  "php${PHP_VERSION}-curl" \
  "php${PHP_VERSION}-zip" \
  "php${PHP_VERSION}-bcmath" \
  "php${PHP_VERSION}-intl" \
  "php${PHP_VERSION}-gd" \
  "php${PHP_VERSION}-redis"

# Composer
if ! command -v composer >/dev/null 2>&1; then
  curl -sS https://getcomposer.org/installer | php
  mv composer.phar /usr/local/bin/composer
fi

# Node.js 20
if ! command -v node >/dev/null 2>&1; then
  curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
  apt install -y nodejs
fi

#############################
# DATABASE SETUP
#############################
mysql -u root <<MYSQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
MYSQL

#############################
# APP CODE
#############################
mkdir -p "${APP_DIR}"
chown -R www-data:www-data "${APP_DIR}"

if [ ! -d "${APP_DIR}/.git" ]; then
  rm -rf "${APP_DIR:?}/"*
  git clone --branch "${BRANCH}" "${REPO_URL}" "${APP_DIR}"
else
  cd "${APP_DIR}"
  sudo -u www-data git fetch --all
  sudo -u www-data git checkout "${BRANCH}"
  sudo -u www-data git pull origin "${BRANCH}"
fi

cd "${APP_DIR}"

#############################
# LARAVEL ENV
#############################
if [ ! -f ".env" ]; then
  cp .env.example .env
fi

# Safe update helper
set_env() {
  local key="$1"
  local value="$2"
  if grep -q "^${key}=" .env; then
    sed -i "s|^${key}=.*|${key}=${value}|g" .env
  else
    echo "${key}=${value}" >> .env
  fi
}

set_env "APP_NAME" "\"${APP_NAME}\""
set_env "APP_ENV" "${APP_ENV}"
set_env "APP_DEBUG" "false"
set_env "APP_URL" "https://${DOMAIN}"

set_env "DB_CONNECTION" "mysql"
set_env "DB_HOST" "127.0.0.1"
set_env "DB_PORT" "3306"
set_env "DB_DATABASE" "${DB_NAME}"
set_env "DB_USERNAME" "${DB_USER}"
set_env "DB_PASSWORD" "${DB_PASS}"

set_env "QUEUE_CONNECTION" "database"
set_env "CACHE_STORE" "file"
set_env "SESSION_DRIVER" "database"

# Mail placeholders: update with your real provider after deployment
set_env "MAIL_MAILER" "smtp"
set_env "MAIL_HOST" "smtp.example.com"
set_env "MAIL_PORT" "587"
set_env "MAIL_USERNAME" "your_smtp_username"
set_env "MAIL_PASSWORD" "your_smtp_password"
set_env "MAIL_ENCRYPTION" "tls"
set_env "MAIL_FROM_ADDRESS" "no-reply@${DOMAIN}"
set_env "MAIL_FROM_NAME" "\"${APP_NAME}\""

#############################
# DEPENDENCIES + BUILD
#############################
sudo -u www-data composer install --no-dev --optimize-autoloader --no-interaction

if [ -f package-lock.json ]; then
  sudo -u www-data npm ci
else
  sudo -u www-data npm install
fi
sudo -u www-data npm run build

#############################
# LARAVEL SETUP
#############################
sudo -u www-data php artisan key:generate --force
sudo -u www-data php artisan migrate --force

# Ensure queue tables exist
sudo -u www-data php artisan queue:table || true
sudo -u www-data php artisan queue:failed-table || true
sudo -u www-data php artisan migrate --force

if [ -n "${SUPERADMIN_SEEDER_CLASS}" ]; then
  sudo -u www-data php artisan db:seed --class="${SUPERADMIN_SEEDER_CLASS}" --force || true
fi

sudo -u www-data php artisan storage:link || true
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

chown -R www-data:www-data "${APP_DIR}"
find "${APP_DIR}" -type f -exec chmod 644 {} \;
find "${APP_DIR}" -type d -exec chmod 755 {} \;
chmod -R ug+rwx "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"

#############################
# NGINX
#############################
cat >/etc/nginx/sites-available/vigu.conf <<NGINX
server {
    listen 80;
    server_name ${DOMAIN} ${WWW_DOMAIN};

    root ${APP_DIR}/public;
    index index.php index.html;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX

ln -sf /etc/nginx/sites-available/vigu.conf /etc/nginx/sites-enabled/vigu.conf
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl restart nginx
systemctl enable nginx

systemctl enable "php${PHP_VERSION}-fpm"
systemctl restart "php${PHP_VERSION}-fpm"

#############################
# SSL
#############################
certbot --nginx -d "${DOMAIN}" -d "${WWW_DOMAIN}" --non-interactive --agree-tos -m "${LETSENCRYPT_EMAIL}" --redirect

#############################
# QUEUE WORKER (systemd)
#############################
cat >/etc/systemd/system/vigu-queue.service <<SYSTEMD
[Unit]
Description=Vigu Laravel Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=5
WorkingDirectory=${APP_DIR}
ExecStart=/usr/bin/php artisan queue:work --queue=emails,default --sleep=3 --tries=3 --timeout=120 --max-time=3600
StandardOutput=append:/var/log/vigu-queue.log
StandardError=append:/var/log/vigu-queue-error.log

[Install]
WantedBy=multi-user.target
SYSTEMD

systemctl daemon-reload
systemctl enable vigu-queue
systemctl restart vigu-queue

#############################
# SCHEDULER (systemd timer)
#############################
cat >/etc/systemd/system/vigu-scheduler.service <<SYSTEMD
[Unit]
Description=Run Laravel Scheduler

[Service]
Type=oneshot
User=www-data
Group=www-data
WorkingDirectory=${APP_DIR}
ExecStart=/usr/bin/php artisan schedule:run
SYSTEMD

cat >/etc/systemd/system/vigu-scheduler.timer <<SYSTEMD
[Unit]
Description=Run Laravel Scheduler Every Minute

[Timer]
OnBootSec=1min
OnUnitActiveSec=1min
Unit=vigu-scheduler.service

[Install]
WantedBy=timers.target
SYSTEMD

systemctl daemon-reload
systemctl enable vigu-scheduler.timer
systemctl restart vigu-scheduler.timer

#############################
# FIREWALL
#############################
ufw allow OpenSSH || true
ufw allow 'Nginx Full' || true
ufw --force enable || true

echo "Deployment complete."
echo "Open: https://${DOMAIN}"
echo "Check queue: systemctl status vigu-queue"
echo "Check scheduler: systemctl status vigu-scheduler.timer"
After script finishes:

Update SMTP values in .env (Resend/Brevo/etc.)
Run:
Bash

cd /var/www/vigu
sudo -u www-data php artisan config:cache
sudo systemctl restart vigu-queue
