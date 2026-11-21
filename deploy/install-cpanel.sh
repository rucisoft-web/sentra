#!/usr/bin/env bash
# =====================================================================
#  SENTRA — skrip instalasi untuk Terminal cPanel
#  Jalankan dari folder aplikasi:   bash deploy/install-cpanel.sh
# =====================================================================
set -e
cd "$(dirname "$0")/.."
APP_DIR="$(pwd)"

# --- pilih PHP CLI >= 8.3 (cPanel EasyApache menyimpan beberapa versi) ---
PHP_BIN="${PHP_BIN:-}"
if [ -z "$PHP_BIN" ]; then
  for c in /opt/cpanel/ea-php84/root/usr/bin/php /opt/cpanel/ea-php83/root/usr/bin/php /usr/local/bin/ea-php84 /usr/local/bin/ea-php83 php; do
    if command -v "$c" >/dev/null 2>&1 && "$c" -r 'exit(version_compare(PHP_VERSION,"8.3.0",">=")?0:1);' 2>/dev/null; then PHP_BIN="$c"; break; fi
  done
fi
if [ -z "$PHP_BIN" ]; then
  echo "✗ PHP 8.3+ tidak ditemukan. Aktifkan ea-php83/ea-php84 di MultiPHP Manager, atau jalankan: PHP_BIN=/path/ke/php bash deploy/install-cpanel.sh"
  exit 1
fi
echo "✓ Memakai PHP: $PHP_BIN ($("$PHP_BIN" -r 'echo PHP_VERSION;'))"

for ext in pdo_mysql mbstring openssl fileinfo tokenizer xml ctype; do
  "$PHP_BIN" -m | grep -qi "^$ext$" || { echo "✗ Ekstensi PHP '$ext' belum aktif (aktifkan di Select PHP Version / EasyApache)."; exit 1; }
done
echo "✓ Ekstensi PHP lengkap"

# --- file .env ---
if [ ! -f .env ]; then
  cp deploy/.env.cpanel .env
  echo "✓ .env dibuat dari deploy/.env.cpanel"
fi
if ! grep -q '^DB_PASSWORD=.\+' .env; then
  read -r -s -p "Masukkan password database (user gpchotel_sentra): " DBPASS; echo
  # escape karakter khusus untuk sed
  ESC=$(printf '%s' "$DBPASS" | sed -e 's/[\/&|]/\\&/g')
  sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=\"${ESC}\"|" .env
  echo "✓ Password database disimpan di .env"
fi
grep -q '^APP_KEY=base64' .env || "$PHP_BIN" artisan key:generate --force

# --- izin folder ---
chmod -R 775 storage bootstrap/cache
mkdir -p storage/app/private/archive storage/framework/{cache,sessions,views} storage/logs
chmod 600 .env

# --- database ---
"$PHP_BIN" artisan config:clear
echo "→ Menguji koneksi database…"
"$PHP_BIN" artisan db:show >/dev/null 2>&1 || "$PHP_BIN" artisan migrate:status >/dev/null 2>&1 || true

if [ "${1:-}" = "--reset" ]; then
  echo "⚠ Reset total: semua tabel dihapus lalu diisi ulang data demo."
  "$PHP_BIN" artisan migrate:fresh --seed --force
elif "$PHP_BIN" artisan migrate:status 2>/dev/null | grep -q "create_archive_tables.*Ran"; then
  echo "→ Database sudah terpasang; menjalankan migrasi baru saja (data tidak dihapus)."
  "$PHP_BIN" artisan migrate --force
else
  echo "→ Instalasi pertama: membuat tabel & data demo Grand Pasundan…"
  "$PHP_BIN" artisan migrate --seed --force
fi

# --- optimasi produksi ---
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache

echo ""
echo "======================================================"
echo " ✓ SENTRA terpasang di: $APP_DIR"
echo "   Buka: $(grep '^APP_URL=' .env | cut -d= -f2)"
echo "   Login demo: gm@gpch.test / password"
echo ""
echo " Cron (cPanel → Cron Jobs, setiap menit):"
echo "   $PHP_BIN $APP_DIR/artisan schedule:run >> /dev/null 2>&1"
echo "======================================================"
