FROM dunglas/frankenphp:1-php8.4-alpine

RUN install-php-extensions pdo_pgsql intl zip bcmath opcache
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_MEMORY_LIMIT=-1

WORKDIR /app

# 1) Dependências (camada em cache enquanto o composer.lock não mudar)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# 2) Código da aplicação
COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts --no-interaction \
 && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
 && printf 'opcache.enable=1\nopcache.validate_timestamps=0\nopcache.memory_consumption=192\nexpose_php=0\nmemory_limit=256M\n' > /usr/local/etc/php/conf.d/zz-eduvia.ini

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh && chmod -R ug+rwX storage bootstrap/cache

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
