<?php

namespace App\Filament\Resources\FinancialReports\Pages;

use App\Filament\Resources\FinancialReports\FinancialReportResource;
use Filament\Resources\Pages\EditRecord;

class EditFinancialReport extends EditRecord
{
    protected static string $resource = FinancialReportResource::class;

    protected function getHeaderActions(): array
    {
        return [\Filament\Actions\DeleteAction::make()];
    }
}
