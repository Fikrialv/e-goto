<?php

namespace App\Filament\Resources;

use App\Enums\ReviewStatus;
use App\Filament\Resources\ReviewResource\Pages;
use App\Models\Review;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Moderasi rating & komentar (D11).
 *
 * Admin hanya bisa menyembunyikan atau menampilkan kembali — isi review tidak
 * bisa disunting. Mengubah kalimat orang lain lalu menampilkannya sebagai
 * ucapannya adalah hal yang tidak boleh bisa dilakukan dari panel mana pun.
 */
class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'Penilaian';

    protected static ?string $modelLabel = 'penilaian';

    protected static ?string $pluralModelLabel = 'penilaian';

    protected static ?int $navigationSort = 6;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['trip:id,title', 'user:id,name']))
            ->columns([
                Tables\Columns\TextColumn::make('trip.title')->label('Trip')->wrap()->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label('Peserta')->searchable(),
                Tables\Columns\TextColumn::make('rating')->label('Rating')->badge(),
                Tables\Columns\TextColumn::make('comment')->label('Komentar')->wrap()->limit(120)->placeholder('tanpa komentar'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (ReviewStatus $state): string => $state->label())
                    ->color(fn (ReviewStatus $state): string => $state === ReviewStatus::Published ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('created_at')->label('Masuk')->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(ReviewStatus::cases())
                        ->mapWithKeys(fn (ReviewStatus $status): array => [$status->value => $status->label()])
                        ->all()),
            ])
            ->actions([
                Tables\Actions\Action::make('sembunyikan')
                    ->label('Sembunyikan')
                    ->icon('heroicon-m-eye-slash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Review $record): bool => $record->status === ReviewStatus::Published)
                    ->action(function (Review $record): void {
                        $record->update(['status' => ReviewStatus::Hidden]);

                        Notification::make()->success()->title('Penilaian disembunyikan')->send();
                    }),

                Tables\Actions\Action::make('tampilkan')
                    ->label('Tampilkan')
                    ->icon('heroicon-m-eye')
                    ->visible(fn (Review $record): bool => $record->status === ReviewStatus::Hidden)
                    ->action(function (Review $record): void {
                        $record->update(['status' => ReviewStatus::Published]);

                        Notification::make()->success()->title('Penilaian ditampilkan lagi')->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviews::route('/'),
        ];
    }
}
