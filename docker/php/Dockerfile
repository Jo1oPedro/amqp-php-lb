FROM php:8.5-cli-alpine

# Instalador de extensões (resolve dependências de sistema automaticamente)
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN install-php-extensions \
    amqp \
    pcntl \
    sockets \
    opcache \
    @composer

WORKDIR /app

# Instala dependências antes de copiar o código para aproveitar cache de camadas
COPY composer.json composer.lock* ./
RUN composer install --no-interaction --no-scripts --no-autoloader --prefer-dist 2>/dev/null || true

COPY . .
RUN composer dump-autoload --optimize 2>/dev/null || true

CMD ["php", "-v"]
