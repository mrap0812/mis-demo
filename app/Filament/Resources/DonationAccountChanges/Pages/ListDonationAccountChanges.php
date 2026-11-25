<?php

namespace App\Filament\Resources\DonationAccountChanges\Pages;

use App\Filament\Resources\DonationAccountChanges\DonationAccountChangeResource;
use Filament\Resources\Pages\ListRecords;

class ListDonationAccountChanges extends ListRecords
{
    protected static string $resource = DonationAccountChangeResource::class;
}
