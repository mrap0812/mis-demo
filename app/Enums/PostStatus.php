<?php

namespace App\Enums;

/** Alur konten sesuai spesifikasi §8: draf, ditinjau, perlu revisi, disetujui, terbit, diarsipkan. */
enum PostStatus: string
{
    case Draf = 'draf';
    case Ditinjau = 'ditinjau';
    case PerluRevisi = 'perlu_revisi';
    case Disetujui = 'disetujui';
    case Terbit = 'terbit';
    case Arsip = 'arsip';

    public function label(): string
    {
        return match ($this) {
            self::Draf => 'Draf',
            self::Ditinjau => 'Menunggu tinjauan',
            self::PerluRevisi => 'Perlu revisi',
            self::Disetujui => 'Disetujui',
            self::Terbit => 'Terbit',
            self::Arsip => 'Diarsipkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draf => 'gray',
            self::Ditinjau => 'warning',
            self::PerluRevisi => 'danger',
            self::Disetujui => 'info',
            self::Terbit => 'success',
            self::Arsip => 'gray',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}
