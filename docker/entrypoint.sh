#!/bin/bash
set -e

# Railway задаёт порт динамически через $PORT (иначе 8080)
: "${PORT:=8080}"

# Apache должен слушать этот порт
sed -i "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Убираем предупреждение об имени сервера
echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf
a2enconf servername >/dev/null 2>&1 || true

# Гарантируем ровно один MPM (prefork нужен для mod_php).
# Принудительно удаляем event/worker и включаем prefork на уровне символических ссылок.
rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf \
      /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_worker.conf
a2enmod mpm_prefork >/dev/null 2>&1 || true

exec apache2-foreground
