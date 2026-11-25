<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\PostResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Setiap tulisan baru selalu mulai sebagai draf milik pembuatnya; status hanya berubah lewat tombol alur.
        $data['author_id'] = auth()->id();
        $data['status'] = PostStatus::Draf->value;

        return $data;
    }
}
