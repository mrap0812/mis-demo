#!/usr/bin/env bash
# Membangun paket instalasi cPanel (tanpa SSH):
#   mis-situs-<tgl>.zip   -> ekstrak di folder home akun cPanel (menghasilkan mis-app/ dan public_html/)
#   mis-database-<tgl>.sql -> impor lewat phpMyAdmin ke database BARU
# Kebutuhan: Linux/WSL, PHP 8.3+, composer, MariaDB lokal (untuk menyusun install.sql), zip.
# Pemakaian: deploy/build-cpanel.sh <folder-vendor-no-dev> <folder-keluaran>
set -euo pipefail
SRC="$(cd "$(dirname "$0")/.." && pwd)"
VENDOR="${1:?folder vendor hasil composer install --no-dev}"
OUT="${2:?folder keluaran}"
DATE="$(TZ=Asia/Makassar date +%Y%m%d)"
STAGE="$(mktemp -d)"
APP="$STAGE/mis-app"; WEB="$STAGE/public_html"
DBNAME="mis_build_$$"
mkdir -p "$APP" "$WEB" "$OUT"

echo "1/7 Menyalin kode aplikasi"
rsync -a "$SRC/" "$APP/" \
  --exclude='/vendor' --exclude='/node_modules' --exclude='/public' --exclude='/tests' --exclude='/deploy' \
  --exclude='/.env' --exclude='/.git' --exclude='/storage' --exclude='/database/database.sqlite' \
  --exclude='/assets' --exclude='/*.png' --exclude='/*.html' --exclude='/page.*' --exclude='/*.py' --exclude='/*.js' \
  --exclude='/test.*' --exclude='/AGENTS.md' --exclude='/CLAUDE.md' --exclude='/phpunit.xml' --exclude='/package*.json' \
  --exclude='/.phpunit*' --exclude='/.editorconfig' --exclude='/.gitattributes' --exclude='/.gitignore' --exclude='/.idea' --exclude='/.vscode'
mkdir -p "$APP"/storage/{app/private/backups,app/public,framework/cache/data,framework/sessions,framework/views,logs} "$APP/bootstrap/cache"
rm -f "$APP"/bootstrap/cache/*.php
cp -a "$VENDOR" "$APP/vendor"
# Lapisan pengaman bila folder aplikasi suatu saat ikut berada di folder web.
printf 'Require all denied\nDeny from all\n' > "$APP/.htaccess"

echo "2/7 Menyalin aset publik"
rsync -a "$SRC/public/" "$WEB/" --exclude='/storage' --exclude='/index.php' --exclude='/hot' --exclude='/robots.txt'
cp "$SRC/deploy/stubs/index.php" "$WEB/index.php"
cp "$SRC/deploy/stubs/htaccess" "$WEB/.htaccess"
printf 'User-agent: *\nDisallow: /livewire\nDisallow: /pasang\n\nSitemap: https://www.mis.id/sitemap.xml\n' > "$WEB/robots.txt"
mkdir -p "$WEB/storage"
rsync -a "$SRC/storage/app/public/" "$WEB/storage/"
cp "$SRC/deploy/stubs/storage-htaccess" "$WEB/storage/.htaccess"

echo "3/7 Menyusun file .env produksi (kunci unik paket ini)"
KEY="base64:$(head -c 32 /dev/urandom | base64)"
TOKEN="$(head -c 30 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | head -c 40)"
sed -e "s#__APP_KEY__#${KEY}#" -e "s#__SETUP_TOKEN__#${TOKEN}#" "$SRC/deploy/stubs/env.production" > "$APP/.env"
chmod 640 "$APP/.env"

echo "4/7 Autoload produksi"
( cd "$APP" && COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --no-dev --classmap-authoritative --no-scripts -q )

echo "5/7 Menyusun database awal di MariaDB lokal"
mariadb -e "DROP DATABASE IF EXISTS $DBNAME; CREATE DATABASE $DBNAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
export DB_CONNECTION=mariadb DB_HOST=127.0.0.1 DB_PORT=3306 DB_DATABASE=$DBNAME DB_USERNAME="${BUILD_DB_USER:-mis}" DB_PASSWORD="${BUILD_DB_PASS:-mis}"
( cd "$APP" \
  && php artisan package:discover -q \
  && php artisan migrate --force -q \
  && php artisan db:seed --force -q \
  && php artisan mis:import-wordpress "$SRC/storage/app/_tools/wp-posts.json" "$WEB/storage/wp" \
  && php artisan mis:thumbs \
  && mariadb -e "TRUNCATE TABLE $DBNAME.audit_logs;" \
  && php artisan mis:backup --out="$OUT/mis-database-$DATE.sql" --plain )
mariadb -e "DROP DATABASE $DBNAME;"
unset DB_CONNECTION DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD
# Bersihkan sisa proses build di dalam paket
rm -rf "$APP"/storage/logs/* "$APP"/storage/framework/views/* "$APP"/storage/framework/cache/data/* "$APP"/storage/framework/sessions/* "$APP"/bootstrap/cache/*.php
( cd "$APP" && php artisan package:discover -q ) && rm -f "$APP"/storage/logs/*
for d in "$APP"/storage/{logs,framework/views,framework/sessions,framework/cache/data,app/private/backups}; do touch "$d/.gitkeep"; done

echo "6/7 Panduan"
cp "$SRC/deploy/PANDUAN-INSTALASI.md" "$STAGE/PANDUAN-INSTALASI.md"

echo "7/7 Mengemas ZIP"
# Izin standar cPanel: folder 755, file 644. File yang bisa ditulisi semua orang (777/666) ditolak suEXEC (error 500).
find "$STAGE" -type d -exec chmod 755 {} +
find "$STAGE" -type f -exec chmod 644 {} +
chmod 640 "$APP/.env"
rm -f "$OUT/mis-situs-$DATE.zip"
( cd "$STAGE" && zip -qr -9 "$OUT/mis-situs-$DATE.zip" mis-app public_html PANDUAN-INSTALASI.md )
cp "$SRC/deploy/PANDUAN-INSTALASI.md" "$OUT/"
rm -rf "$STAGE"
ls -la "$OUT"
