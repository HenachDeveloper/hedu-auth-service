#!/bin/sh
set -e
cd /app
# Passo opcional por servico (ex.: gerar chaves RSA no auth-service)
eval "${PRE_START:-true}"
until php artisan migrate --force; do echo "[boot] a aguardar a base de dados..."; sleep 3; done
if [ "${SEED_ON_BOOT:-false}" = "true" ]; then php artisan db:seed --force; fi
php artisan config:cache >/dev/null 2>&1 || true
php artisan route:cache >/dev/null 2>&1 || true
exec frankenphp run --config /etc/caddy/Caddyfile --adapter caddyfile
