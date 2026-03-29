FROM php:8.4-cli

WORKDIR /app

RUN apt-get update && apt-get install -y unzip zip --no-install-recommends && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

COPY composer.json composer.lock* ./
RUN composer install --no-interaction

COPY app app
COPY test test

CMD ["php", "app/ui/cli.php"]