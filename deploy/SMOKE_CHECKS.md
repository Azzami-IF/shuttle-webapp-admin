Smoke-checks for shuttle-admin-panel deployment

Quick tests to run after deploy (run from a machine with network access):

1) Basic site is reachable
```
curl -I -L https://admin.example.com/
```

2) Confirm `public/` assets load
```
curl -fsS https://admin.example.com/js/app.js -o /dev/null && echo OK
```

3) Login flow (simulate form POST; replace creds)
```
curl -v -c /tmp/cookies.txt -d "email=admin@example.com&password=password" -X POST https://admin.example.com/admin/login -L
```
Expect HTTP 302 redirect to dashboard on success.

4) Check admin→API connectivity (server-to-server token)
```
curl -v -H "Accept: application/json" "${REMOTE_API_BASE:-https://api.ambatu.my.id/api}/admin/dashboard/stats" \
  -H "Authorization: Bearer $REMOTE_API_TOKEN" || echo "Remote API may require auth/session"
```

5) Verify logs for errors
```
tail -n 200 storage/logs/laravel.log
```

6) Artisan checks (if available)
```
php artisan config:cache
php artisan route:list --path=admin
```

If any step fails, capture output and check `storage/logs/laravel.log` and webserver error logs.
