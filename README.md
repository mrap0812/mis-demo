# Website & Panel Masjid Imam Syafi'i Banjarmasin

Tahap 1 (bagian pertama): modul **Kajian** dan **Berita & Artikel** dengan panel admin.
Stack: Laravel 13, Filament 5 (Livewire 4), SQLite untuk pengembangan, PHP 8.4.

## Menjalankan di komputer lokal (Windows)

```powershell
cd C:\Users\USER\Herd\mis
php artisan serve --host=127.0.0.1 --port=8010
```

- Situs publik: http://127.0.0.1:8010
- Panel admin: http://127.0.0.1:8010/admin

Akun uji **hanya dibuat di lingkungan `local`/`testing`** oleh seeder (`database/seeders/DatabaseSeeder.php`):
`admin@mis.test`, `editor@mis.test`, `peninjau@mis.test`, `bendahara@mis.test`, kata sandi `password`.
Menjalankan seeder ulang mengatur ulang kata sandi akun uji, sehingga sesi yang sedang login ikut keluar.
Untuk produksi, jangan jalankan seeder akun; buat akun lewat `php artisan make:filament-user` lalu atur perannya di menu Pengguna.

Mengulang database dari nol: `php artisan migrate:fresh --seed`
Menjalankan tes: `php artisan test`

## Yang sudah bisa dikelola dari panel

| Menu | Isi | Catatan |
|---|---|---|
| Kajian › Jadwal kajian | Seri kajian rutin, berkala (pekan ke-n), tentatif | Hari, waktu (ba'da Subuh/Ashar/Maghrib, Jumat), pemateri, kitab dan judul Arab, warna, aturan pekan |
| Kajian › Pertemuan & pengumuman | Data per tanggal | Judul pertemuan dari poster, pemateri pengganti, **batal/tunda dengan alasan**, poster, tautan rekaman |
| Kajian › Pemateri | Nama baku dan nama ringkas | |
| Konten › Berita & artikel | Berita, pengumuman, artikel | Alur **draf → ditinjau → (perlu revisi) → terbit → arsip** |
| Situs › Pengaturan situs | Teks beranda, pita pengumuman (dengan tanggal berakhir), nomor kontak, tombol WA, alamat, kode pos, tautan siaran/playlist/grup, akun media sosial, teks footer | Hanya admin; setiap perubahan tercatat di log aktivitas |
| Situs › Halaman | Profil dan halaman tambahan (`/halaman/{alamat}`) | Diubah peninjau/admin; alamat `/profil` dikunci |
| Situs › Layanan jamaah | Nama, ikon, ringkasan, syarat & alur, jam, kontak, tautan daftar, urutan | Diubah peninjau/admin; status "menunggu pengurus" tampil sebagai label |
| Infak › Rekening & QRIS | Rekening dan QRIS yang tampil di `/infak` | Tidak bisa diubah langsung; hanya "Ajukan baru / perubahan / nonaktif" (admin & bendahara) |
| Infak › Persetujuan infak | Antrean pengajuan | Disetujui **orang kedua** (admin/bendahara, bukan pengaju) dengan mengetik ulang nomor rekening dari sumber asli, atau mencentang QRIS sudah dipindai |
| (penjaga) Nomor rekening di teks bebas | Berita, halaman, layanan, pita pengumuman | Halaman/layanan/pengumuman **menolak** nomor mirip rekening yang belum disetujui di menu Infak; menerbitkan berita yang memuatnya wajib dicentang peninjau dan tercatat di catatan tinjauan; dasbor menandai berita terbit yang memuatnya (`app/Support/AccountNumberGuard.php`, heuristik) |
| Pengguna | Akun dan peran | Hanya terlihat oleh admin |

## Aturan yang diterapkan (dan diuji di `tests/Feature/MisRulesTest.php`)

- Editor menulis dan mengajukan; **tidak bisa menerbitkan**. Peninjau/admin menerbitkan, **tidak boleh menyetujui tulisannya sendiri**.
- Draf dan tulisan berjadwal tidak tampil di situs publik sebelum waktunya.
- Isi artikel disanitasi (Symfony HtmlSanitizer) sebelum ditampilkan.
- Jadwal shalat: algoritma astronomis parameter Kemenag RI, koordinat masjid dari peta mis.id; cocok dengan jadwal Bimas Islam 17 Apr 2026 (selisih maks. 1 menit). **Menunggu pengesahan takmir.**
- Pekan ke-n dihitung per **bulan Masehi** (asumsi kerja; perlu konfirmasi admin kajian). Kajian berkala Selasa pekan ke-4 diasumsikan menggantikan kajian rutin di slot yang sama.
- Batal/tunda satu pertemuan tidak mengubah pertemuan lain dalam seri.
- Zona waktu aplikasi: Asia/Makassar (WITA).

## Sumber data awal (seeder)

- Jadwal: poster jadwal resmi @kajianmisbjm, update Shafar 1448 H.
- Judul pertemuan 5 s.d. 12 Okt 2026: poster per pertemuan @kajianmisbjm dan rekaman YouTube.
- Berita: 6 artikel arsip mis.id beserta foto sampulnya. Isi lengkap tetap di tautan sumber.

## Belum dikerjakan (backlog Tahap 1 dan berikutnya)

- Halaman Program dan Transparansi (masih di prototipe; butuh data keuangan nyata, bukan simulasi).
- Isi Profil yang belum diterima dari pengurus: sejarah lengkap, visi misi, fasilitas, struktur pengurus.
- Rekening resmi: satu pengajuan dari poster (BSI operasional) menunggu dicocokkan bendahara dengan buku rekening. Rekening kurban (BSI 700-7000-479, dari pengumuman mis.id 16 Sep 2026) belum diajukan.
- Modul keuangan (Tahap 2 s.d. 4).
- MFA admin, Content-Security-Policy, backup terjadwal, deployment ke server dan domain mis.id.
- Saat deploy: tambahkan baris `Sitemap: https://mis.id/sitemap.xml` di `public/robots.txt` (harus URL absolut).
- Konfirmasi pengurus: izin foto, ejaan baku pemateri, alamat/kode pos, jam mulai kajian.

## Migrasi dari WordPress mis.id (13 Okt 2026)

- Isi lengkap tulisan diimpor dari dump database WordPress (`wpgq_*`): 12 terbit + 2 draf, beserta foto (diperkecil ke lebar maks. 1600 px) dan PDF buletin, ke `storage/app/public/wp/`.
  Perintah (aman diulang, tidak membuat duplikat, tidak menimpa judul/sampul yang sudah dirapikan):
  `php artisan mis:import-wordpress storage/app/_tools/wp-posts.json <folder wp-content/uploads>`
- Alamat lama tetap berfungsi (`Route::fallback` + `config/mis.php`): `mis.id/{slug-lama}/` dialihkan 301 ke `/berita/{slug}`; `/donor-darah`, `/survey`, `/ihsan`, `/penggiatmis`, `/panitiakurban` dialihkan 302 ke Google Form masing-masing; `/wp-content/uploads/...` ke salinan lokal.
- Hosting saat ini: cPanel CloudLinux, LiteSpeed, MariaDB 10.11. Laravel 13 butuh PHP 8.3 ke atas: pastikan versi ini tersedia di PHP Selector sebelum pindah.

## Performa (12 Okt 2026)

- Font disajikan sendiri dari `public/fonts` (Fontsource, OFL): subset Latin untuk teks Indonesia, subset Arab untuk teks Arab; tidak ada permintaan ke Google. Amiri (teks Arab panjang) hanya terunduh di halaman yang memuatnya.
- CSS dibersihkan dengan `storage/app/_tools/css-purge.php` (membuang aturan yang kelas/id-nya tidak ada di views/JS/app). Jalankan ulang setelah menambah banyak gaya baru, lalu bandingkan tampilan. Cadangan sebelum pembersihan: `storage/app/_tools/site.before-purge.css`.
- Berat halaman (ponsel, tanpa cache, tanpa kompresi server): sekitar 225 KB untuk halaman biasa, 310 sampai 370 KB untuk halaman berteks Arab panjang; sebelumnya sekitar 615 KB. Di server produksi, aktifkan gzip/brotli dan cache panjang untuk `/fonts`, `/css`, `/images`.

## Fitur jamaah (diuji di `tests/Feature/JamaahFeaturesTest.php`)

- `/jadwal-shalat?bulan=YYYY-MM`: tabel sebulan (WITA + Hijriah Umm al-Qura), maksimal 12 bulan ke belakang/depan, bisa dicetak.
- `/kajian.ics` (opsional `?jenis=kajian|berkala|muslimah|khutbah`): kalender langganan 60 hari ke depan; batal dikirim sebagai `STATUS:CANCELLED`. Tombol Google Calendar/iPhone hanya berfungsi setelah situs bisa diakses publik (Google tidak bisa membaca `127.0.0.1`).
- `public/manifest.webmanifest` + ikon: situs bisa ditambahkan ke layar utama ponsel.
- Audit aksesibilitas: axe-core (WCAG 2.1 AA) 0 pelanggaran di semua halaman publik, mode terang dan gelap. Alat audit disimpan di `storage/app/_tools`, tidak ikut ke folder publik.

## Audit 12 Okt 2026 (diuji di `tests/Feature/AuditHardeningTest.php`)

- Kebijakan akses per model: hanya admin yang mengelola akun, menghapus seri kajian, pemateri, dan membuka log aktivitas.
- Log aktivitas (Pengguna › Log aktivitas): mencatat buat/ubah/hapus/pulihkan pada tulisan, kajian, pemateri, akun; tidak menyimpan kata sandi; tidak bisa diubah atau dihapus.
- Header keamanan (nosniff, SAMEORIGIN, Referrer-Policy, Permissions-Policy, HSTS saat HTTPS, noindex untuk /admin).
- Agenda 14 hari di panel: membatalkan atau mengisi detail satu pertemuan dengan formulir yang sudah terisi.
- Data terstruktur schema.org (Mosque, Event dengan status batal/tunda, NewsArticle), `sitemap.xml`, halaman 404.

## Deploy ke cPanel (tanpa SSH)

- Paket dibangun dengan `deploy/build-cpanel.sh` (Linux/WSL + MariaDB lokal + vendor hasil `composer install --no-dev`). Hasil: `mis-situs-TGL.zip` (mis-app/ + public_html/) dan `mis-database-TGL.sql` (phpMyAdmin).
- Langkah pemasangan untuk pengurus: `deploy/PANDUAN-INSTALASI.md`.
- `composer.json` dikunci ke platform PHP 8.3 agar paket jalan di hosting PHP 8.3 maupun 8.4.
- Panel > Situs > Pemeliharaan (admin saja): kesehatan sistem, cadangan database (SQL murni, tanpa exec/mysqldump), migrasi, optimasi, pembersihan, mode perbaikan buatan sendiri (panel tetap bisa dibuka), ukuran folder, error terbaru. Semua tindakan tercatat di Log aktivitas (jenis Sistem).
- Akun admin pertama: halaman `/pasang` dengan `SETUP_TOKEN`, mati sendiri setelah ada pengguna.
- `ADMIN_PATH` mengatur alamat panel (paket produksi: `kelola-mis`). `PUBLIC_STORAGE_IN_WEBROOT=true` menyimpan unggahan langsung di `public_html/storage` (tanpa symlink).
- Panel admin tidak lagi memuat sumber pihak ketiga: font disajikan sendiri, avatar inisial dibuat di server (bawaan Filament mengirim nama pengguna ke ui-avatars.com).