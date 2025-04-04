# ✅ Use PHP 8.2 with FPM
FROM php:8.2-fpm

# ✅ Set the working directory
WORKDIR /var/www/html

# ✅ Install required system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
  libpng-dev \
  libjpeg-dev \
  libfreetype6-dev \
  zip \
  unzip \
  curl \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install gd pdo pdo_mysql

# ✅ Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ✅ Copy Laravel application files
COPY . .

# ✅ Install PHP dependencies (optimized for production)
RUN composer install --no-dev --optimize-autoloader

# ✅ Set folder permissions
RUN chmod -R 775 storage bootstrap/cache

# ✅ Generate APP_KEY (will silently skip if already set)
RUN php artisan key:generate || true

# ✅ Clear and cache config, routes, and application cache
RUN php artisan config:clear || true && \
    php artisan cache:clear || true && \
    php artisan route:clear || true && \
    php artisan config:cache && \
    php artisan route:cache

# ✅ Expose port Laravel runs on
EXPOSE 8000

# ✅ Start Laravel server
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
