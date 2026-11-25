<?php

namespace App\Filament\Resources\KajianSessions\Pages;

use App\Filament\Resources\KajianSessions\KajianSessionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKajianSession extends EditRecord
{
    protected static string $resource = KajianSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
