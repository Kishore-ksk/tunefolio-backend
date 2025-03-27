# Use PHP 8.1 with FPM
FROM php:8.1-fpm

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

# Give storage permission
RUN chmod -R 775 storage bootstrap/cache

# Expose the port for Laravel
EXPOSE 8000

# Start Laravel
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
