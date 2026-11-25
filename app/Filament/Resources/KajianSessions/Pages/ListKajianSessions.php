<?php

namespace App\Filament\Resources\KajianSessions\Pages;

use App\Filament\Resources\KajianSessions\KajianSessionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKajianSessions extends ListRecords
{
    protected static string $resource = KajianSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
