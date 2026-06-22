Deployment checklist — shuttle-admin-panel

1) Server requirements
- PHP 8.0+ with PDO, mbstring, OpenSSL, fileinfo
- Composer vendor uploaded (or run `composer install`) and Node assets built (optional)
- DocumentRoot set to project `public/` (cPanel: point domain to `public_html/.../public`)

2) Prepare `.env` (use `.env.production` or set env vars in panel)
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY=` — generate with `php artisan key:generate` (or copy from backend if shared)
- `REMOTE_API_BASE=https://api.ambatu.my.id/api`
- `REMOTE_API_TOKEN=` (optional service token)
- DB connection: admin UI may not require a full DB; if present ensure credentials are correct

3) File permissions
- Ensure `storage/` and `bootstrap/cache/` are writable by webserver user

4) CPanel / limited hosts notes
- If host lacks Composer/npm, upload pre-built `vendor/` and `public/build` assets (zipped). Extract as the cPanel user to avoid permission issues.
- Avoid running `chown` on shared hosting.

5) Post-deploy commands (run as site user)
```
php artisan config:clear
php artisan route:cache
php artisan view:clear
php artisan optimize
```

6) Security
- Keep `APP_DEBUG=false` in production. Do NOT commit `.env` to git.

7) Rollback
- Keep a zip of the previous release so you can restore quickly.
