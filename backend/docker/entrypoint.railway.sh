#!/bin/bash
set -e

cd /app

# Sinh APP_KEY nếu chưa có (Railway nên set sẵn qua biến môi trường)
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force || true
fi

# Chờ MySQL sẵn sàng rồi chạy migration
echo "Đang chờ database và chạy migration..."
for i in $(seq 1 30); do
    if php artisan migrate --force 2>/dev/null; then
        echo "Migration xong."
        break
    fi
    echo "Chưa kết nối được DB, thử lại ($i)..."
    sleep 3
done

# Tạo symlink storage và cache cấu hình
php artisan storage:link || true
php artisan config:cache || true
php artisan route:cache || true

# Chạy server trên cổng Railway cấp ($PORT)
echo "Khởi động Laravel trên cổng ${PORT}..."
exec php artisan serve --host=0.0.0.0 --port="${PORT}"
