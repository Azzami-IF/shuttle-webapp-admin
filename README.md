Laravel-admin — local verification

Quick steps to run migrations locally (Windows PowerShell):

1. Change to project folder

```powershell
cd c:\Program1\Shuttle\Laravel-admin
```

2. Install PHP dependencies

```powershell
composer install
```

3. Copy example env and create SQLite file

```powershell
copy .env.example .env
New-Item -ItemType Directory -Path database -Force
New-Item -ItemType File -Path database\database.sqlite -Force
```

4. Generate app key and run migrations

```powershell
php artisan key:generate
php artisan migrate
```

Notes:
- If you prefer MySQL update DB_* values in `.env` accordingly.
- If `composer` is blocked in PowerShell, try running from Git Bash or enable scripts per your policy.
# Laravel Admin (skeleton)

Ini adalah skeleton project Laravel untuk admin panel yang akan dipisah dari codebase utama.

Langkah singkat untuk menyiapkan (di server/developer machine):

1. Clone repo baru (atau gunakan folder ini sebagai starting point).
2. Jalankan `composer install`.
3. Salin `.env` dari repo lama atau buat baru, kemudian jalankan `php artisan key:generate`.
4. Sesuaikan `APP_URL` ke `https://admin.ambatu.my.id` dan `SESSION_DOMAIN=.ambatu.my.id`.

Catatan: file ini hanya skeleton — Anda perlu memindahkan controller, view, route, dan asset dari repo Laravel utama.
