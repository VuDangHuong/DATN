# ============================================================
#  Stage 1: Build frontend assets (Vite)
# ============================================================
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm install
COPY resources ./resources
COPY vite.config.js ./
RUN npm run build

# ============================================================
#  Stage 2: PHP dependencies (Composer)
# ============================================================
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# Cài dependency, bỏ qua scripts vì code app chưa được copy ở bước này.
# --ignore-platform-reqs: bỏ qua kiểm tra extension (gd, ...) vì image composer
# không có sẵn; extension thật được cài đầy đủ ở image "app" bên dưới.
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs

# ============================================================
#  Stage 3: Application image (PHP 8.3 + FPM)
# ============================================================
FROM php:8.3-fpm-alpine AS app

# Cài các gói hệ thống và extension PHP cần thiết cho dự án
RUN apk add --no-cache \
        bash git curl libpng-dev libjpeg-turbo-dev freetype-dev \
        libzip-dev oniguruma-dev icu-dev openssl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql mbstring bcmath gd zip exif pcntl intl

# OpenSSL là extension dựng sẵn trong PHP; xác nhận có mặt cho chức năng ký số
RUN php -m | grep -i openssl

# Mang binary composer từ image composer sang để chạy dump-autoload
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy mã nguồn ứng dụng
COPY . .

# Copy vendor (từ stage composer) và assets đã build (từ stage node)
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

# Hoàn tất autoload và tối ưu
RUN composer dump-autoload --optimize --no-dev

# Phân quyền cho storage và cache
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Entrypoint xử lý migrate + cache khi container khởi động
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]