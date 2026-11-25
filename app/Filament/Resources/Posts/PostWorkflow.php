<?php

namespace App\Filament\Resources\Posts;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Support\AccountNumberGuard;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\Text;
use Illuminate\Support\HtmlString;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/** Tombol alur konten: draf -> ditinjau -> (perlu revisi | terbit) -> arsip. Hak tiap tombol diatur PostPolicy. */
class PostWorkflow
{
    public static function actions(): array
    {
        return [self::submit(), self::requestRevision(), self::publish(), self::archive()];
    }

    public static function submit(): Action
    {
        return Action::make('submit')
            ->label('Ajukan tinjauan')
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->color('warning')
            ->visible(fn (Post $record) => auth()->user()->can('submit', $record))
            ->requiresConfirmation()
            ->modalDescription('Tulisan akan dikunci dari penyuntingan sampai peninjau memberi keputusan.')
            ->action(function (Post $record) {
                $record->update(['status' => PostStatus::Ditinjau, 'review_note' => null]);
                Notification::make()->title('Diajukan untuk ditinjau')->success()->send();
            });
    }

    public static function requestRevision(): Action
    {
        return Action::make('requestRevision')
            ->label('Minta revisi')
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('danger')
            ->visible(fn (Post $record) => auth()->user()->can('review', $record))
            ->schema([
                Textarea::make('review_note')->label('Catatan untuk penulis')->required()->rows(4),
            ])
            ->action(function (Post $record, array $data) {
                $record->update([
                    'status' => PostStatus::PerluRevisi,
                    'review_note' => $data['review_note'],
                    'reviewer_id' => auth()->id(),
                ]);
                Notification::make()->title('Dikembalikan ke penulis')->send();
            });
    }

    public static function publish(): Action
    {
        return Action::make('publish')
            ->label('Setujui & terbitkan')
            ->icon(Heroicon::OutlinedCheckBadge)
            ->color('success')
            ->visible(fn (Post $record) => auth()->user()->can('publish', $record))
            ->requiresConfirmation()
            ->modalDescription('Pastikan isi, rujukan dalil, dan foto sudah diperiksa. Tulisan langsung tampil di website.')
            ->schema(fn (Post $record) => ($found = AccountNumberGuard::scan($record->title, $record->excerpt, $record->body)) ? [
                Text::make(new HtmlString('<div style="padding:.75rem;border-radius:.5rem;background:rgba(220,38,38,.1);color:rgb(185,28,28)"><b>Perhatian:</b> tulisan ini memuat nomor yang mirip rekening dan belum disetujui di menu Infak: '
                    .e(AccountNumberGuard::describe($found)).'. Sebaiknya minta penulis menggantinya dengan tautan ke halaman /infak.</div>')),
                Checkbox::make('ack_accounts')->label('Saya sudah memastikan nomor tersebut benar milik masjid dan tetap ingin menerbitkan.')->accepted(),
            ] : [])
            ->action(function (Post $record) {
                $found = AccountNumberGuard::scan($record->title, $record->excerpt, $record->body);
                $record->update([
                    'status' => PostStatus::Terbit,
                    'reviewer_id' => auth()->id(),
                    'published_at' => $record->published_at ?? now(),
                    'review_note' => $found
                        ? 'Diterbitkan dengan nomor mirip rekening yang belum terdaftar di menu Infak: '.AccountNumberGuard::describe($found).' (dikonfirmasi peninjau).'
                        : $record->review_note,
                ]);
                Notification::make()->title('Terbit')->success()->send();
            });
    }

    public static function archive(): Action
    {
        return Action::make('archive')
            ->label('Arsipkan')
            ->icon(Heroicon::OutlinedArchiveBox)
            ->color('gray')
            ->visible(fn (Post $record) => auth()->user()->isReviewer() && $record->status === PostStatus::Terbit)
            ->requiresConfirmation()
            ->action(function (Post $record) {
                $record->update(['status' => PostStatus::Arsip]);
                Notification::make()->title('Diarsipkan, tidak tampil lagi di website')->send();
            });
    }
}
