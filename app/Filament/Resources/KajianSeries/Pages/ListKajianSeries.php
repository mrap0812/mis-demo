<?php

namespace App\Filament\Resources\KajianSeries\Pages;

use App\Filament\Resources\KajianSeries\KajianSeriesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKajianSeries extends ListRecords
{
    protected static string $resource = KajianSeriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
