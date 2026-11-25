# Panduan instalasi situs baru mis.id (cPanel, tanpa SSH)

Paket ini mengganti WordPress di www.mis.id dengan situs baru (Laravel). WordPress **tidak dihapus**, hanya dipindah ke folder arsip, sehingga bisa dikembalikan kapan saja.

Perkiraan waktu: 30 sampai 45 menit. Lakukan saat pengunjung sepi (misalnya selepas Isya).

## Isi paket

| File | Untuk apa |
|---|---|
| `mis-situs-TANGGAL.zip` | Kode situs. Diekstrak di folder home cPanel, menghasilkan `mis-app/` dan `public_html/` |
| `mis-database-TANGGAL.sql` | Isi database awal (jadwal kajian, 14 tulisan lama, pengaturan). Diimpor lewat phpMyAdmin |
| `PANDUAN-INSTALASI.md` | Panduan ini |

> **Rahasia:** di dalam ZIP ada file `mis-app/.env` berisi kunci enkripsi unik dan token penyiapan. Jangan bagikan ZIP ini ke grup atau orang lain.

Susunan akhir di server:

```
/home/misid/
├── mis-app/          kode aplikasi (TIDAK bisa dibuka dari browser)
├── public_html/      folder web: index.php, css, js, gambar, storage/
└── arsip-wordpress/  WordPress lama (cadangan)
```

---

## Langkah 1. Cadangkan WordPress lama

1. cPanel > **phpMyAdmin** > pilih database WordPress (`misid_wp745`) > tab **Export** > **Go**. Simpan file .sql di komputer.
2. cPanel > **File Manager** > klik kanan `public_html` > **Compress** > Zip. Unduh hasilnya ke komputer. (Sekitar 1 GB, karena folder `wp-content/uploads` berisi 901 MB foto dan video.)

Dua file inilah jalan pulang bila ada masalah.

## Langkah 2. Ubah versi PHP mis.id ke 8.3 atau 8.4

1. cPanel > **MultiPHP Manager**.
2. Centang **hanya** `mis.id` (jangan ubah `admin.mis.id`, `fundraising.mis.id`, `tpq.mis.id`).
3. Pilih **PHP 8.4** (atau 8.3 bila 8.4 tidak ada) > **Apply**.
4. Bila ada menu **Select PHP Version** atau **PHP Extensions**, pastikan aktif: `pdo_mysql`, `mbstring`, `openssl`, `gd`, `intl`, `fileinfo`, `zip`, `sodium`.

WordPress lama tetap jalan di PHP 8.3/8.4, jadi langkah ini aman dilakukan lebih dulu.

## Langkah 3. Buat database baru dan impor isinya

1. cPanel > **MySQL Databases**:
   - Create New Database: misalnya `misid_web` > Create.
   - Add New User: misalnya `misid_webuser`, pakai **Password Generator**. **Catat kata sandinya.**
   - Add User To Database: pilih user dan database tadi > **ALL PRIVILEGES** > Make Changes.
2. cPanel > **phpMyAdmin** > klik database `misid_web` (yang BARU, bukan database WordPress) > tab **Import** > pilih `mis-database-TANGGAL.sql` > **Import**.
3. Berhasil bila muncul pesan hijau dan ada sekitar 20 tabel (antara lain `posts`, `kajian_series`, `settings`).

## Langkah 4. Pindahkan WordPress ke arsip

1. File Manager > folder home (`/home/misid`) > **+ Folder** > `arsip-wordpress`.
2. Buka `public_html`. Klik **Settings** (kanan atas) > centang **Show Hidden Files** > Save.
3. Pilih **semua** isi `public_html` **kecuali** folder `.well-known` (dipakai untuk sertifikat SSL; biarkan di tempat).
4. **Move** ke `/home/misid/arsip-wordpress`.

Penting: file `.htaccess` lama ikut dipindah. File itu memanggil `aios-bootstrap.php` (firewall plugin lama); bila tertinggal, situs baru akan error.

Mulai saat ini sampai Langkah 6 selesai, situs tidak bisa dibuka. Itu normal.

## Langkah 5. Unggah dan ekstrak paket

1. File Manager > masuk ke `/home/misid` (folder home, BUKAN di dalam `public_html`).
2. **Upload** > pilih `mis-situs-TANGGAL.zip`. Tunggu sampai 100%.
3. Klik kanan ZIP > **Extract** > tujuan `/home/misid` > Extract.
4. Pastikan sekarang ada `mis-app/` dan `public_html/` berisi `index.php`, `css/`, `js/`, `storage/`, `.htaccess`.
5. ZIP boleh dihapus dari server.

## Langkah 6. Isi data database di file .env

1. Buka `mis-app/` > klik kanan `.env` > **Edit**.
2. Ganti tiga baris ini dengan data dari Langkah 3:
   ```
   DB_DATABASE=misid_web
   DB_USERNAME=misid_webuser
   DB_PASSWORD=kata-sandi-yang-dicatat
   ```
   Bila kata sandi mengandung `#` atau spasi, apit dengan tanda kutip: `DB_PASSWORD="ab#cd ef"`.
3. Perhatikan baris `ADMIN_PATH=kelola-mis`: itu alamat panel admin (`https://www.mis.id/kelola-mis`). Boleh diganti.
4. **Save Changes**.

## Langkah 7. Buat akun admin pertama

1. Di `.env`, salin nilai `SETUP_TOKEN=...` (teks setelah tanda `=`).
2. Buka **https://www.mis.id/pasang**.
3. Isi token, nama, email, dan kata sandi kuat (minimal 12 karakter, ada huruf dan angka). Simpan kata sandi di pengelola kata sandi.
4. Setelah berhasil, halaman `/pasang` hilang dengan sendirinya. Baris `SETUP_TOKEN` di `.env` boleh dihapus.

Akun pengurus lain (editor, peninjau, bendahara) dibuat dari panel: menu **Pengguna**.

## Langkah 8. Periksa kesehatan

1. Masuk ke `https://www.mis.id/kelola-mis`.
2. Menu **Situs > Pemeliharaan**. Semua baris **Kesehatan sistem** seharusnya hijau.
3. Tekan **Buat cadangan sekarang**, lalu **Optimalkan**.
4. Buka beberapa halaman dari HP: beranda, kajian, berita, infak, jadwal shalat.
5. Coba alamat lama, harus pindah otomatis:
   - `https://www.mis.id/qurbanmis2022/` > artikel kurban 2022
   - `https://www.mis.id/donor-darah/` > Google Form donor darah
   - `https://mis.id/` > `https://www.mis.id/`

## Langkah 9 (disarankan). Jadwal otomatis

Tanpa langkah ini situs tetap berjalan. Dengan langkah ini, cadangan database dibuat otomatis setiap hari pukul 02.30 WITA dan sampah dirapikan setiap pekan.

cPanel > **Cron Jobs** > Common Settings: **Once Per Five Minutes** > Command:

```
cd /home/misid/mis-app && /usr/local/bin/php artisan schedule:run >/dev/null 2>&1
```

Bila `/usr/local/bin/php` tidak dikenal, tanyakan ke hosting alamat PHP CLI versi 8.3/8.4 (sering `/opt/cpanel/ea-php84/root/usr/bin/php`).

---

## Pemeliharaan rutin (panel > Pemeliharaan)

| Tombol | Kapan dipakai |
|---|---|
| Buat cadangan sekarang | Sebelum perubahan besar. Unduh cadangan ke komputer minimal sebulan sekali |
| Optimalkan | Setelah instalasi atau pembaruan, agar situs lebih cepat |
| Bersihkan cache | Setelah mengubah `.env` atau mengunggah versi baru |
| Rapikan | Bila log membesar atau database terasa lambat |
| Atur mode perbaikan | Menutup situs sementara untuk pengunjung; panel admin tetap bisa dibuka |
| Jalankan migrasi | Muncul hanya bila versi baru membawa perubahan struktur database. Cadangan dibuat otomatis lebih dulu |

## Memperbarui ke versi berikutnya

1. Panel > Pemeliharaan > **Buat cadangan sekarang**, unduh.
2. Unggah ZIP pembaruan ke `/home/misid`, ekstrak. ZIP pembaruan tidak berisi `.env` dan tidak menimpa gambar unggahan.
3. Panel > Pemeliharaan > **Jalankan migrasi** (bila muncul) > **Bersihkan cache** > **Optimalkan**.

## Kembali ke WordPress (bila terpaksa)

1. Pindahkan isi `public_html` (kecuali `.well-known`) ke folder baru, misalnya `arsip-laravel`.
2. Pindahkan isi `arsip-wordpress` kembali ke `public_html`.
3. Database WordPress tidak pernah disentuh, jadi langsung jalan.

## Masalah umum

| Gejala | Penyebab dan solusi |
|---|---|
| Halaman putih atau "500 Internal Server Error" | (1) PHP masih 8.1: ulangi Langkah 2. (2) Isian database di `.env` salah: periksa Langkah 6. (3) `.htaccess` WordPress lama tertinggal: pastikan isinya adalah versi baru (diawali komentar "Situs Masjid Imam Syafi'i") |
| Gambar berita tidak tampil | Pastikan folder `public_html/storage/wp` ada. Panel > Pemeliharaan > baris "Folder gambar unggahan" harus hijau |
| "Too many redirects" | Domain lewat Cloudflare mode SSL Flexible. Ubah ke Full di Cloudflare, atau jangan aktifkan aturan "Paksa HTTPS" di `.htaccess` |
| `/pasang` menampilkan 404 | Sudah ada akun (halaman sengaja dimatikan), atau `SETUP_TOKEN` kosong atau kurang dari 24 karakter |
| Lupa kata sandi admin | Admin lain mengatur ulang lewat menu Pengguna. Bila tidak ada admin lain, hubungi pengembang |

## Setelah dua pekan berjalan stabil

- Folder `arsip-wordpress` (sekitar 1,2 GB) boleh diunduh sebagai ZIP lalu dihapus dari server untuk membebaskan kuota. Foto yang dipakai di 14 tulisan lama sudah disalin dan diperkecil ke `public_html/storage/wp` (4,3 MB).
- Database WordPress boleh dihapus **setelah** file ekspor dari Langkah 1 disimpan di dua tempat.
- Gambar lama yang tidak ikut dipindah (misalnya foto dari tulisan yang tidak dimigrasikan) akan tampil "tidak ditemukan" bila dibuka dari tautan lama.
