<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Support\Maintenance\DatabaseBackup;
use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    protected $signature = 'mis:backup
        {--out= : Tulis ke path ini (misalnya untuk paket instalasi)}
        {--plain : Tanpa kompresi gzip}
        {--keep= : Jumlah cadangan yang disimpan (bawaan dari config mis.backup_keep)}';

    protected $description = 'Buat cadangan database (SQL, bisa diimpor lewat phpMyAdmin) dan hapus cadangan lama';

    public function handle(): int
    {
        $path = DatabaseBackup::create($this->option('out') ?: null, ! $this->option('plain'));
        $this->info('Cadangan dibuat: '.$path);
        if (! $this->option('out')) {
            $pruned = DatabaseBackup::prune((int) ($this->option('keep') ?: config('mis.backup_keep')));
            $pruned && $this->line("{$pruned} cadangan lama dihapus.");
            AuditLog::system('backup', 'Cadangan database otomatis', ['file' => basename($path)]);
        }

        return self::SUCCESS;
    }
}
