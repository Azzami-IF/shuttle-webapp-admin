Checklist cepat diagnosis 500 / autoload errors

1) Pastikan `Document Root` mengarah ke folder `public` project
   - contoh: `/home/ambt1462/public_html/admin-panel-shuttle/public`

2) Pastikan `vendor/autoload.php` ada
   - `ls -la /home/ambt1462/public_html/admin-panel-shuttle/vendor/autoload.php`

3) Periksa file `index.php` yang dilayani web (pastikan relatif path benar)
   - `sed -n '1,120p' /home/ambt1462/public_html/admin-panel-shuttle/public/index.php`

4) Periksa Laravel log dan webserver log
   - `tail -n 80 storage/logs/laravel.log`
   - `tail -n 80 /home/ambt1462/public_html/error_log`

5) Jalankan perintah artisan jika belum:
   - `php artisan key:generate --force`
   - `php artisan migrate --force`
   - `php artisan storage:link`
   - `php artisan config:cache && php artisan route:cache && php artisan view:cache`

6) Set permission
   - `chown -R ambt1462:ambt1462 /home/ambt1462/public_html/admin-panel-shuttle/storage /home/ambt1462/public_html/admin-panel-shuttle/bootstrap/cache`
   - `find storage -type d -exec chmod 775 {} \;` and `find storage -type f -exec chmod 664 {} \;`

7) Jika masih 500, ambil pesan terakhir dari `storage/logs/laravel.log` dan `error_log` lalu kirim ke saya.

Example one-liner to extract package, set permissions and run artisan (run as server user):
```bash
cd /home/ambt1462/public_html/admin-panel-shuttle || exit
unzip -o /tmp/shuttle-admin-package.zip -d .
chown -R ambt1462:ambt1462 storage bootstrap/cache vendor node_modules public/build
find storage -type d -exec chmod 775 {} \;
find storage -type f -exec chmod 664 {} \;
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link || true
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
