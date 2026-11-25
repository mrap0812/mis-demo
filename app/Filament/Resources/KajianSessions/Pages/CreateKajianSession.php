<?php

namespace App\Filament\Resources\KajianSessions\Pages;

use App\Filament\Resources\KajianSessions\KajianSessionResource;
use App\Models\KajianSession;
use Filament\Resources\Pages\CreateRecord;

class CreateKajianSession extends CreateRecord
{
    protected static string $resource = KajianSessionResource::class;

    /** Prefill dari halaman Agenda: ?series=ID&date=YYYY-MM-DD&status=dibatalkan */
    protected function fillForm(): void
    {
        $date = request()->query('date');
        $status = request()->query('status');
        $this->form->fill(array_filter([
            'kajian_series_id' => request()->integer('series') ?: null,
            'date' => is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : null,
            'status' => is_string($status) && array_key_exists($status, KajianSession::STATUSES) ? $status : 'terjadwal',
            'source' => 'admin',
        ]));
    }

    protected function getRedirectUrl(): string
    {
        return \App\Filament\Pages\Agenda::getUrl();
    }
}
