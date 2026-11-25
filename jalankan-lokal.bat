@echo off
title Website Masjid Imam Syafi'i (localhost)
cd /d "%~dp0"

echo ============================================
echo  Website Masjid Imam Syafi'i - localhost
echo ============================================

where php >nul 2>nul || (echo [!] PHP tidak ditemukan. Pastikan Herd sudah terpasang. & pause & exit /b 1)

echo [1/3] Membersihkan cache tampilan...
php artisan optimize:clear >nul

echo [2/3] Menjalankan pembaruan database bila ada...
php artisan migrate --force

echo [3/3] Menyalakan server di http://127.0.0.1:8010
start "" "http://127.0.0.1:8010"
echo.
echo  Biarkan jendela ini terbuka selama memakai situs.
echo  Tutup jendela ini (atau tekan Ctrl+C) untuk mematikan server.
echo.
php artisan serve --host=127.0.0.1 --port=8010
pause
