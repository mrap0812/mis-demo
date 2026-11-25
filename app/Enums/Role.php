<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Editor = 'editor';
    case Peninjau = 'peninjau';
    case Bendahara = 'bendahara';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Editor => 'Editor konten',
            self::Peninjau => 'Peninjau konten agama',
            self::Bendahara => 'Bendahara (rekening & QRIS)',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}
