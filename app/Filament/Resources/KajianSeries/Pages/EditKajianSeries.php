<?php

namespace App\Filament\Resources\KajianSeries\Pages;

use App\Filament\Resources\KajianSeries\KajianSeriesResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKajianSeries extends EditRecord
{
    protected static string $resource = KajianSeriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
