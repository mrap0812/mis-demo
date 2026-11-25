<?php

namespace App\Filament\Resources\Speakers\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SpeakerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama lengkap dengan gelar')->required()->helperText('Ikuti ejaan baku yang disepakati pengurus.'),
            TextInput::make('short_name')->label('Nama ringkas')->placeholder('Ust. ...'),
            Textarea::make('bio')->label('Profil singkat')->rows(4)->columnSpanFull(),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }
}
