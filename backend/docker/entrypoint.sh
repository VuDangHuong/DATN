#!/bin/bash
set -e

cd /var/www/html

# Tạo APP_KEY nếu chưa có
if [ -z "$APP_KEY" ] && ! grep -q "APP_KEY=base64" .env 2>/dev/null; then
    php artisan key:generate --force || true
fi

# Chờ MySQL sẵn sàng (tối đa ~60s)
echo "Đang chờ kết nối cơ sở dữ liệu..."
for i in $(seq 1 30); do
    if php artisan migrate:status >/dev/null 2>&1; then
        break
    fi
    sleep 2
done

# Chạy migration
php artisan migrate --force || true

# Tạo symbolic link cho storage (ảnh, tệp upload)
php artisan storage:link || true

# Tối ưu cache cấu hình / route / view
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Thực thi lệnh chính (php-fpm)
exec "$@"
