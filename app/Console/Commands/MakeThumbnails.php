<?php

namespace App\Console\Commands;

use App\Support\Img;
use Illuminate\Console\Command;

class MakeThumbnails extends Command
{
    protected $signature = 'mis:thumbs';

    protected $description = 'Buat versi kecil (240 sampai 960 px) semua gambar unggahan agar halaman lebih ringan di ponsel';

    public function handle(): int
    {
        $r = Img::makeAll();
        $this->info("{$r['files']} gambar diperiksa, {$r['made']} versi kecil dibuat".(Img::webp() ? ' (WebP).' : ' (JPEG; server tanpa WebP).'));

        return self::SUCCESS;
    }
}
