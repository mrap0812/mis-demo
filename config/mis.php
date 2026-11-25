<?php

/*
 * Alamat lama mis.id (WordPress) yang harus tetap berfungsi setelah pindah sistem.
 * Tautan ini kemungkinan sudah tersebar di WhatsApp/Instagram. Sumber: database WordPress, 13 Okt 2026.
 * Tulisan lama (mis.id/{slug}/) dialihkan otomatis lewat kolom source_url, tidak perlu didaftarkan di sini.
 */
return [
    // Alamat panel admin. Bisa diganti (misalnya 'kelola-mis') untuk mengurangi serangan tebak login otomatis.
    'admin_path' => trim(env('ADMIN_PATH', 'admin'), '/') ?: 'admin',

    // Token penyiapan akun admin pertama (halaman /pasang). Hanya berlaku selama belum ada satu pun pengguna.
    'setup_token' => env('SETUP_TOKEN'),

    // Jumlah file cadangan database yang disimpan; yang lebih lama dihapus otomatis.
    'backup_keep' => (int) env('BACKUP_KEEP', 14),

    'legacy_redirects' => [
        'donor-darah' => 'https://docs.google.com/forms/d/e/1FAIpQLSer0QfVt0jRGDrxBdcVNAx1kG7OCmInc2k1IBjKMDMhDyGRfg/viewform',
        'survey' => 'https://docs.google.com/forms/d/e/1FAIpQLSfmj3TGymcR1y4CRn9_fkQi13zxx1g_rM1UgJKyZ4DXyg5Ung/viewform',
        'ihsan' => 'https://docs.google.com/forms/d/e/1FAIpQLScdVC4uf0GxwK0ubb4VNLlka_jtQclYpPQJhWFj7aRq6RGI-A/viewform',
        'penggiatmis' => 'https://docs.google.com/forms/d/e/1FAIpQLSfMAXro4j0-ihWaYutCXDqCwtOeSla5KgRF6GjjdWjS_GoprA/viewform',
        'panitiakurban' => 'https://docs.google.com/forms/d/e/1FAIpQLScRDreOgSQmauOrFULj9k-3HGg3mwFZXHUle1me6DzXK49zyQ/viewform',
        'beranda-masjid' => '/',
        'category/berita-kegiatan' => '/berita?jenis=berita',
        'category/artikel' => '/berita?jenis=artikel',
    ],
];
