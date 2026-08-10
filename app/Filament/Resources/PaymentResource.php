<?php

namespace App\Filament\Resources;

use App\Enums\PaymentStatus;
use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Antrean verifikasi pembayaran manual.
 *
 * Sengaja tanpa create/edit: baris pembayaran hanya lahir dari unggahan
 * customer, dan satu-satunya perubahan yang boleh dilakukan admin adalah
 * menyetujui atau menolak — lewat layar verifikasi yang menampilkan bukti
 * berdampingan dengan nominal seharusnya.
 */
class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Verifikasi Pembayaran';

    protected static ?string $modelLabel = 'pembayaran';

    protected static ?string $pluralModelLabel = 'pembayaran';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('booking.code')
                    ->label('Kode booking')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('booking.user.name')
                    ->label('Pemesan')
                    ->searchable()
                    ->description(fn (Payment $record): string => $record->booking->user->email),

                Tables\Columns\TextColumn::make('booking.schedule.trip.title')
                    ->label('Trip')
                    ->limit(30)
                    ->description(fn (Payment $record): string => $record->booking->schedule->start_date->translatedFormat('j M Y')),

                Tables\Columns\TextColumn::make('booking.total_amount')
                    ->label('Nominal seharusnya')
                    // Rupiah disimpan utuh (bukan sen), jadi tanpa pembagi.
                    ->money('IDR')
                    ->description(fn (Payment $record): string => 'kode unik '.$record->booking->unique_code),

                Tables\Columns\IconColumn::make('is_duplicate_flagged')
                    ->label('Duplikat')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->trueColor('danger')
                    ->falseIcon('heroicon-o-minus-small')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (PaymentStatus $state): string => match ($state) {
                        PaymentStatus::Pending => 'Menunggu',
                        PaymentStatus::Verified => 'Disetujui',
                        PaymentStatus::Rejected => 'Ditolak',
                    })
                    ->color(fn (PaymentStatus $state): string => match ($state) {
                        PaymentStatus::Pending => 'warning',
                        PaymentStatus::Verified => 'success',
                        PaymentStatus::Rejected => 'danger',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diunggah')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        PaymentStatus::Pending->value => 'Menunggu',
                        PaymentStatus::Verified->value => 'Disetujui',
                        PaymentStatus::Rejected->value => 'Ditolak',
                    ])
                    // Antrean kerja admin adalah yang belum diputuskan; sisanya
                    // riwayat yang bisa dibuka kalau memang dicari.
                    ->default(PaymentStatus::Pending->value),

                Tables\Filters\TernaryFilter::make('is_duplicate_flagged')
                    ->label('Ditandai duplikat'),
            ])
            ->actions([
                Tables\Actions\Action::make('verifikasi')
                    ->label('Periksa')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Payment $record): string => Pages\ReviewPayment::getUrl(['record' => $record])),
            ])
            ->bulkActions([]);
    }

    /**
     * @return Builder<Payment>
     */
    public static function getEloquentQuery(): Builder
    {
        // Tabel menampilkan kolom dari 4 tabel lain — tanpa eager load ini,
        // satu halaman antrean memicu puluhan query tambahan.
        return parent::getEloquentQuery()->with(['booking.user', 'booking.schedule.trip']);
    }

    public static function getNavigationBadge(): ?string
    {
        $menunggu = static::getModel()::where('status', PaymentStatus::Pending)->count();

        return $menunggu > 0 ? (string) $menunggu : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'review' => Pages\ReviewPayment::route('/{record}/periksa'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
