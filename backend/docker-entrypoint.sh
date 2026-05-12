#!/bin/sh
set -e

PORT="${PORT:-80}"

echo "=== ClasseMorta backend boot ==="
echo "PORT=${PORT}"

# ---- 1) Forza un unico MPM (prefork) ----
echo "[mpm] mods-enabled (prima):"
ls /etc/apache2/mods-enabled/ | grep -i mpm || true

rm -f /etc/apache2/mods-enabled/mpm_event.load   \
      /etc/apache2/mods-enabled/mpm_event.conf   \
      /etc/apache2/mods-enabled/mpm_worker.load  \
      /etc/apache2/mods-enabled/mpm_worker.conf  \
      /etc/apache2/mods-enabled/mpm_prefork.load \
      /etc/apache2/mods-enabled/mpm_prefork.conf

a2enmod mpm_prefork >/dev/null

echo "[mpm] mods-enabled (dopo):"
ls /etc/apache2/mods-enabled/ | grep -i mpm || true

# ---- 2) Sostituisce la porta di Apache con $PORT ----
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s|<VirtualHost \*:[0-9]\+>|<VirtualHost *:${PORT}>|" \
    /etc/apache2/sites-available/000-default.conf

echo "[apache] listen su ${PORT}"
echo "=== avvio Apache ==="

exec "$@"
