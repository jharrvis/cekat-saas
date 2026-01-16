# 🚀 Panduan Deploy Cekat.ai ke Production Server

**Domain:** cekat.biz.id  
**Control Panel:** HestiaCP  
**Stack:** PHP 8.2+, MySQL/MariaDB, Node.js, Nginx

---

## 📋 Daftar Isi

1. [Persiapan Server](#1-persiapan-server)
2. [Setup Domain di HestiaCP](#2-setup-domain-di-hestiacp)
3. [Clone Repository](#3-clone-repository)
4. [Install Dependencies](#4-install-dependencies)
5. [Konfigurasi Environment](#5-konfigurasi-environment)
6. [Setup Database](#6-setup-database)
7. [Build Assets](#7-build-assets)
8. [Konfigurasi Nginx](#8-konfigurasi-nginx)
9. [Finalisasi](#9-finalisasi)
10. [Setup Cron Jobs](#10-setup-cron-jobs)
11. [Konfigurasi Email (Gmail SMTP)](#11-konfigurasi-email-gmail-smtp)
12. [Konfigurasi Midtrans](#12-konfigurasi-midtrans)
13. [Konfigurasi Google SSO](#13-konfigurasi-google-sso-login-dengan-google)
14. [Troubleshooting](#14-troubleshooting)

---

## 1. Persiapan Server

### Pastikan PHP Extensions Terinstall:
```bash
sudo apt update
sudo apt install php8.2-cli php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-bcmath php8.2-curl php8.2-zip php8.2-gd
```

### Install Composer (jika belum ada):
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Install Node.js (jika belum ada):
```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

---

## 2. Setup Domain di HestiaCP

1. Login ke **HestiaCP** → `https://your-server-ip:8083`
2. Klik **Add Web Domain**
3. Masukkan domain: `cekat.biz.id`
4. Centang ✅ **Enable SSL** (Let's Encrypt)
5. Klik **Save**

---

## 3. Clone Repository

```bash
# Masuk ke direktori web
cd /home/admin/web/cekat.biz.id/public_html

# Hapus file default
rm -rf *

# Clone repository
git clone https://github.com/jharrvis/cekat-saas.git .
```

---

## 4. Install Dependencies

```bash
# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Install Node dependencies & build
npm install
npm run build
```

---

## 5. Konfigurasi Environment

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### Edit file `.env`:
```bash
nano .env
```

```env
APP_NAME="Cekat.ai"
APP_ENV=production
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxx
APP_DEBUG=false
APP_URL=https://cekat.biz.id

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cekat_db
DB_USERNAME=cekat_user
DB_PASSWORD=your_secure_password

# Session & Cache
SESSION_DRIVER=database
CACHE_DRIVER=file
QUEUE_CONNECTION=database

# Mail (Gmail SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@cekat.biz.id
MAIL_FROM_NAME="Cekat.ai"

# Midtrans
MIDTRANS_SERVER_KEY=your-server-key
MIDTRANS_CLIENT_KEY=your-client-key
MIDTRANS_IS_PRODUCTION=true

# OpenRouter AI
OPENROUTER_API_KEY=your-openrouter-key
```

---

## 6. Setup Database

### Buat Database di HestiaCP:
1. HestiaCP → **Databases** → **Add Database**
2. Database Name: `cekat_db`
3. Username: `cekat_user`
4. Password: `your_secure_password`
5. Klik **Save**

### Jalankan Migrasi:
```bash
php artisan migrate --force
php artisan db:seed --force
```

---

## 7. Build Assets

```bash
# Build production assets
npm run build

# Buat storage link
php artisan storage:link

# Clear & optimize cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

---

## 8. Konfigurasi Nginx

### Edit Nginx Config:
```bash
sudo nano /home/admin/conf/web/cekat.biz.id/nginx.conf
```

Ganti isi dengan:
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name cekat.biz.id www.cekat.biz.id;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name cekat.biz.id www.cekat.biz.id;

    root /home/admin/web/cekat.biz.id/public_html/public;
    index index.php;

    ssl_certificate /home/admin/conf/web/cekat.biz.id/ssl/cekat.biz.id.pem;
    ssl_certificate_key /home/admin/conf/web/cekat.biz.id/ssl/cekat.biz.id.key;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Widget CORS headers
    location /widget/ {
        add_header 'Access-Control-Allow-Origin' '*' always;
        add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS' always;
        add_header 'Access-Control-Allow-Headers' 'Content-Type' always;
    }

    location /api/ {
        add_header 'Access-Control-Allow-Origin' '*' always;
        add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS' always;
        add_header 'Access-Control-Allow-Headers' 'Content-Type, Authorization' always;
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

### Restart Nginx:
```bash
sudo systemctl restart nginx
```

---

## 9. Finalisasi

### Set Permissions:
```bash
cd /home/admin/web/cekat.biz.id/public_html

# Set ownership
sudo chown -R admin:admin .

# Set directory permissions
find . -type d -exec chmod 755 {} \;

# Set file permissions
find . -type f -exec chmod 644 {} \;

# Set storage & cache writable
chmod -R 775 storage bootstrap/cache
```

### Test Aplikasi:
```bash
# Test artisan
php artisan about

# Test database connection
php artisan migrate:status
```

---

## 10. Setup Cron Jobs

### Edit Crontab:
```bash
crontab -e
```

### Tambahkan:
```cron
# Laravel Scheduler - Jalankan setiap menit
* * * * * cd /home/admin/web/cekat.biz.id/public_html && php artisan schedule:run >> /dev/null 2>&1

# Queue Worker (optional)
# * * * * * cd /home/admin/web/cekat.biz.id/public_html && php artisan queue:work --stop-when-empty >> /dev/null 2>&1
```

---

## 11. Konfigurasi Email (Gmail SMTP)

### Buat App Password Gmail:
1. Buka https://myaccount.google.com/security
2. Aktifkan **2-Step Verification** jika belum
3. Klik **App passwords**
4. Pilih **Mail** → **Other (Custom name)** → "Cekat.ai"
5. Copy password yang dihasilkan (16 karakter)
6. Masukkan ke `.env` sebagai `MAIL_PASSWORD`

### Test Email:
```bash
php artisan tinker
>>> Mail::raw('Test email', fn($m) => $m->to('your@email.com')->subject('Test'));
```

---

## 12. Konfigurasi Midtrans

### Setup Production:
1. Login ke https://dashboard.midtrans.com
2. Pilih **Production**
3. Copy **Server Key** dan **Client Key**
4. Masukkan ke `.env`
5. Di Midtrans Dashboard → **Settings** → **Payment** → **Notification URL**:
   ```
   https://cekat.biz.id/api/payment/notification
   ```

---

## 13. Konfigurasi Google SSO (Login dengan Google)

### Langkah 1: Buat Project di Google Cloud Console

1. Buka https://console.cloud.google.com
2. Klik **Select a project** → **New Project**
3. Nama project: `Cekat.ai`
4. Klik **Create**

### Langkah 2: Aktifkan Google+ API

1. Di sidebar, pilih **APIs & Services** → **Library**
2. Cari **Google+ API** atau **Google People API**
3. Klik **Enable**

### Langkah 3: Buat OAuth Credentials

1. **APIs & Services** → **Credentials** → **Create Credentials** → **OAuth client ID**
2. Jika diminta, konfigurasi **OAuth consent screen** terlebih dahulu:
   - **User Type**: External
   - **App name**: Cekat.ai
   - **User support email**: your-email@gmail.com
   - **Developer contact**: your-email@gmail.com
   - Klik **Save and Continue** sampai selesai
3. Kembali ke **Credentials** → **Create Credentials** → **OAuth client ID**
4. **Application type**: Web application
5. **Name**: Cekat.ai Web Client
6. **Authorized JavaScript origins**:
   ```
   https://cekat.biz.id
   ```
7. **Authorized redirect URIs**:
   ```
   https://cekat.biz.id/auth/google/callback
   ```
8. Klik **Create**
9. **Simpan Client ID dan Client Secret**

### Langkah 4: Konfigurasi .env

```bash
nano .env
```

Tambahkan:
```env
# Google SSO
GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=https://cekat.biz.id/auth/google/callback
```

### Langkah 5: Clear Config Cache

```bash
php artisan config:cache
```

### Test Login Google:

1. Buka https://cekat.biz.id/login
2. Klik tombol **Login dengan Google**
3. Login dengan akun Google
4. User akan ter-redirect ke dashboard

### Troubleshooting Google SSO:

**Error: redirect_uri_mismatch**
- Pastikan redirect URI di Google Console **sama persis** dengan `.env`
- Termasuk trailing slash - jika ada/tidak ada harus konsisten

**Error: Access blocked**
- Pastikan OAuth consent screen sudah di-publish (bukan Test mode)
- Atau tambahkan email tester di OAuth consent screen → Test users

---

## 14. Troubleshooting

### 500 Internal Server Error:
```bash
# Check Laravel log
tail -f storage/logs/laravel.log

# Check Nginx error log
tail -f /var/log/nginx/error.log

# Fix permissions
chmod -R 775 storage bootstrap/cache
```

### Halaman Blank / Assets Tidak Load:
```bash
# Rebuild assets
npm run build

# Clear cache
php artisan optimize:clear
php artisan config:cache
```

### Database Connection Error:
```bash
# Test connection
php artisan db:monitor

# Check credentials in .env
cat .env | grep DB_
```

### Cron Tidak Jalan:
```bash
# Test scheduler
php artisan schedule:run

# Check cron logs
grep CRON /var/log/syslog
```

### Widget Tidak Berfungsi (CORS):
```bash
# Pastikan Nginx config sudah include CORS headers
# Restart nginx setelah edit config
sudo systemctl restart nginx
```

---

## ✅ Checklist Deployment

- [ ] Server requirements terpenuhi
- [ ] Domain pointing ke server
- [ ] SSL aktif (HTTPS)
- [ ] Repository cloned
- [ ] Composer & NPM dependencies installed
- [ ] `.env` configured
- [ ] Database created & migrated
- [ ] Assets built (`npm run build`)
- [ ] Storage linked
- [ ] Permissions set correctly
- [ ] Nginx configured
- [ ] Cron job added
- [ ] Email SMTP configured
- [ ] Midtrans webhook URL set
- [ ] Tested homepage
- [ ] Tested login/register
- [ ] Tested payment
- [ ] Tested chatbot widget

---

## 📞 Support

Jika ada masalah saat deployment, cek:
1. Laravel log: `storage/logs/laravel.log`
2. Nginx log: `/var/log/nginx/error.log`
3. PHP-FPM log: `/var/log/php8.2-fpm.log`

**Repository:** https://github.com/jharrvis/cekat-saas
