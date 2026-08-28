<?php

namespace App\Filament\Vendor\Resources;

use App\Enums\BookingStatus;
use App\Filament\Vendor\Resources\BookingResource\Pages;
use App\Models\Booking;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Peserta yang memesan trip mitra (D9). Read-only: mitra melihat siapa yang
 * ikut dan kapan, tapi tidak menyentuh status booking maupun pembayaran —
 * jalur uang tetap milik admin lewat layar verifikasi D5.
 *
 * Nomor identitas peserta sengaja tidak ditampilkan di sini. Mitra butuh nama
 * dan kontak untuk mengurus keberangkatan; NIK/paspor tidak menambah apa pun
 * selain risiko kalau layar ini terbuka di lapangan.
 */
class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Peserta';

    protected static ?string $modelLabel = 'booking';

    protected static ?string $pluralModelLabel = 'booking';

    protected static ?int $navigationSort = 3;

    /**
     * @return Builder<Booking>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('schedule.trip', fn (Builder $query) => $query->where('vendor_id', auth()->id()));
    }

    /**
     * Badge = booking yang masuk seminggu terakhir. Ini pengganti notifikasi
     * booking baru: tidak ada kanal push/email di V1.5, jadi kabarnya menunggu
     * mitra membuka panel — bukan dijanjikan sampai sendiri.
     */
    public static function getNavigationBadge(): ?string
    {
        $baru = static::getEloquentQuery()
            ->where('created_at', '>=', now()->subWeek())
            ->whereIn('status', [BookingStatus::AwaitingVerification, BookingStatus::Confirmed])
            ->count();

        return $baru > 0 ? (string) $baru : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->emptyStateHeading('Belum ada pemesanan')
            ->emptyStateDescription('Pemesanan trip Anda akan tampil di sini beserta kontak pesertanya.')
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['schedule.trip', 'user']))
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('schedule.trip.title')
                    ->label('Trip')
                    ->wrap()
                    ->description(fn (Booking $record): string => $record->schedule->start_date->translatedFormat('j M Y')),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pemesan')
                    ->description(fn (Booking $record): string => $record->user?->phone ?? 'nomor belum diisi'),
                Tables\Columns\TextColumn::make('pax_count')
                    ->label('Peserta')
                    ->numeric(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (BookingStatus $state): string => $state->label()),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Masuk')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(BookingStatus::cases())
                        ->mapWithKeys(fn (BookingStatus $status): array => [$status->value => $status->label()])
                        ->all()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
        ];
    }
}
