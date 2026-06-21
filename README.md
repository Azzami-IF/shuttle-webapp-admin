shuttle-admin-panel — Admin panel for Shuttle (extracted)

Overview
 - This repository contains the extracted admin panel for the Shuttle project. It is intended to run independently and, when available, use the central API at `REMOTE_API_BASE`.

Quick setup (Windows)

1) Change to project folder

```powershell
cd C:\Program1\Shuttle\shuttle-admin-panel
```

2) Install PHP dependencies

```powershell
composer install
```

3) Copy example env and create SQLite file (optional)

```powershell
copy .env.example .env
New-Item -ItemType Directory -Path database -Force
New-Item -ItemType File -Path database\database.sqlite -Force
```

4) Configure `.env` and generate key

 - Set `APP_URL`, `DB_CONNECTION`, and `REMOTE_API_BASE` (e.g. `https://api.ambatu.my.id/api`).
 - Example: `REMOTE_API_BASE=https://api.ambatu.my.id/api`

```powershell
php artisan key:generate
php artisan migrate
```

How the admin connects to data
 - The admin panel prefers the remote API configured by `REMOTE_API_BASE` (see `.env`). Controllers will attempt API requests first, and fall back to local database models if the remote API is unreachable.

Authentication
 - The login form posts to `/login` and uses the app's authentication. If you want to test locally, create a local admin user or run seeds.

Creating a local admin user (quick)

```powershell
php artisan tinker
>>> \App\Models\User::factory()->create(['email'=>'admin@example.com','password'=>bcrypt('password'),'role'=>'admin']);
```

Notes
 - If `composer` is blocked in PowerShell, try Git Bash or run PowerShell with appropriate execution policy.
 - Logs: check `storage/logs/laravel.log` for bootstrap or request errors.
 - The project was pushed to GitHub: https://github.com/Azzami-IF/shuttle-webapp-admin

Next steps
 - Update remaining controllers to use `REMOTE_API_BASE` when appropriate.
 - Add deployment instructions and secrets management for production.

Contact
 - If you want, I can update controllers to point to the API, create seeds for local testing, or add CI workflow to deploy.
