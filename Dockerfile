# 11x11 — PHP 8.2 + Apache для Railway / любого Docker-хостинга
FROM php:8.2-apache

# Расширения: PDO MySQL и mbstring (нужны приложению)
RUN apt-get update \
 && apt-get install -y --no-install-recommends libonig-dev \
 && docker-php-ext-install pdo_mysql mbstring \
 && a2enmod rewrite \
 && rm -rf /var/lib/apt/lists/*

# Apache: оставить ровно один MPM (prefork нужен для mod_php)
RUN a2dismod mpm_event mpm_worker 2>/dev/null; a2enmod mpm_prefork 2>/dev/null; true

# Код приложения
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

# Запуск через entrypoint (Apache слушает порт из $PORT)
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8080
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
