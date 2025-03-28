# Use PHP 8.2 with FPM
FROM php:8.2-fpm


# Set the working directory inside the container
WORKDIR /var/www/html

# Install necessary PHP extensions
RUN apt-get update && apt-get install -y \
  libpng-dev \
  libjpeg-dev \
  libfreetype6-dev \
  zip \
  unzip \
  curl \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install gd pdo pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy Laravel app files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Set environment and permissions
RUN chmod -R 775 storage bootstrap/cache

# Ensure APP_KEY exists before running Artisan commands
RUN php artisan key:generate || true

# Run Artisan commands (ignore failures)
RUN php artisan config:clear || true && \
    php artisan cache:clear || true && \
    php artisan config:cache || true

# Set Laravel environment (Render will handle .env separately)
RUN php artisan config:cache

# Expose the port Laravel will run on
EXPOSE 8000

RUN php artisan migrate --force


# Start Laravel Server
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
