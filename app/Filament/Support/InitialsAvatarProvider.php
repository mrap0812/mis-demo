<?php

namespace App\Filament\Support;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Avatar inisial dibuat di server (SVG data URI). Pengganti bawaan Filament yang memanggil ui-avatars.com,
 * yang berarti mengirim nama pengurus ke pihak ketiga setiap kali panel dibuka.
 */
class InitialsAvatarProvider implements AvatarProvider
{
    public function get(Model|Authenticatable $record): string
    {
        $initials = collect(preg_split('/\s+/u', trim((string) Filament::getNameForDefaultAvatar($record))))
            ->map(fn ($w) => mb_substr(preg_replace('/^[^\p{L}\p{N}]+/u', '', $w), 0, 1))
            ->filter()->take(2)->join('');
        $initials = htmlspecialchars(mb_strtoupper($initials ?: '?'), ENT_XML1);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" fill="#5A2226"/>'
            .'<text x="32" y="32" dy=".35em" text-anchor="middle" font-family="sans-serif" font-size="26" font-weight="600" fill="#fff">'.$initials.'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
