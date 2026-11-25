<?php

namespace App\Filament\Resources\Posts\Tables;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\PostWorkflow;
use App\Models\Post;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                ImageColumn::make('cover')->label('')->disk('public')->square()->size(48),
                TextColumn::make('title')->label('Judul')->searchable()->limit(60)->wrap()
                    ->description(fn (Post $r) => Post::TYPES[$r->type] ?? $r->type),
                TextColumn::make('category')->label('Kategori')->badge()->color('gray'),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (PostStatus $state) => $state->label())
                    ->color(fn (PostStatus $state) => $state->color()),
                TextColumn::make('published_at')->label('Terbit')->dateTime('d M Y')->sortable(),
                TextColumn::make('author.name')->label('Penulis')->placeholder('Impor mis.id'),
                TextColumn::make('reviewer.name')->label('Peninjau')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(PostStatus::options()),
                SelectFilter::make('type')->label('Jenis')->options(Post::TYPES),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make(PostWorkflow::actions()),
            ]);
    }
}
