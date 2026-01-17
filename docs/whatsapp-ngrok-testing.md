# Testing WhatsApp Integration with Ngrok

## Masalah
Webhook dari Fonnte tidak bisa mencapai server lokal Anda karena domain `cekat-saas.test` hanya bisa diakses dari komputer Anda.

## Solusi: Gunakan Ngrok

### 1. Install Ngrok

Download dari [ngrok.com/download](https://ngrok.com/download) atau via Chocolatey:
```bash
choco install ngrok
```

### 2. Jalankan Ngrok

```bash
ngrok http 80
```

Output akan menampilkan URL publik seperti:
```
Forwarding: https://abc123.ngrok-free.app -> http://localhost:80
```

### 3. Update APP_URL di .env

Buka file `.env` dan set `APP_URL` ke URL ngrok:
```env
APP_URL=https://abc123.ngrok-free.app
```

### 4. Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
```

### 5. Buat Device Baru

Sekarang buat device WhatsApp baru. Webhook URL yang di-set akan menggunakan domain ngrok, misalnya:
```
https://abc123.ngrok-free.app/api/whatsapp/webhook/5
```

### 6. Atau Update Webhook Manual di Fonnte

Jika device sudah dibuat sebelumnya:

1. Login ke [md.fonnte.com](https://md.fonnte.com)
2. Pilih device Anda
3. Klik **Setting** → **Webhook**
4. Masukkan URL: `https://abc123.ngrok-free.app/api/whatsapp/webhook/{device_id}`
5. Klik **Save**

### 7. Test

1. Kirim pesan WhatsApp ke nomor yang terhubung
2. Cek terminal ngrok - akan muncul request masuk
3. Cek log Laravel: `storage/logs/laravel.log`

---

## Catatan Penting

- **Ngrok URL berubah setiap restart** (kecuali pakai paid plan)
- Setiap ganti URL ngrok, Anda perlu update:
  - `.env` → `APP_URL`
  - Webhook URL di Fonnte

## Alternatif Lain

- **LocalTunnel**: `npx localtunnel --port 80`
- **Cloudflare Tunnel**: [developers.cloudflare.com/cloudflare-one/connections/connect-apps/](https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/)
- **Deploy ke VPS**: Deploy aplikasi ke server dengan IP publik/domain
