<?php

namespace App\Filament\Vendor\Resources;

use App\Enums\ReviewStatus;
use App\Filament\Vendor\Resources\ReviewResource\Pages;
use App\Models\Review;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Rating trip milik mitra (D11). Read-only: mitra melihat penilaian tripnya
 * sendiri, tapi tidak bisa menyembunyikan atau menyunting — moderasi ada di
 * admin, supaya penilaian buruk tidak bisa dihapus oleh yang dinilai.
 *
 * Review yang sudah disembunyikan admin tetap tidak tampil di sini.
 */
class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'Penilaian';

    protected static ?string $modelLabel = 'penilaian';

    protected static ?string $pluralModelLabel = 'penilaian';

    protected static ?int $navigationSort = 4;

    /**
     * @return Builder<Review>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status', ReviewStatus::Published)
            ->whereHas('trip', fn (Builder $query) => $query->where('vendor_id', auth()->id()));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['trip:id,title', 'user:id,name']))
            ->columns([
                Tables\Columns\TextColumn::make('trip.title')->label('Trip')->wrap()->searchable(),
                Tables\Columns\TextColumn::make('rating')->label('Rating')->badge()->sortable(),
                Tables\Columns\TextColumn::make('comment')->label('Komentar')->wrap()->placeholder('tanpa komentar'),
                Tables\Columns\TextColumn::make('user.name')->label('Peserta'),
                Tables\Columns\TextColumn::make('created_at')->label('Masuk')->since()->sortable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviews::route('/'),
        ];
    }
}
