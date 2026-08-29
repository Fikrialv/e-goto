<?php

namespace App\Filament\Resources;

use App\Actions\ProcessRefundRequest;
use App\Enums\AdminScope;
use App\Enums\RefundStatus;
use App\Enums\RefundType;
use App\Filament\Concerns\DibatasiScopeAdmin;
use App\Filament\Resources\RefundRequestResource\Pages;
use App\Models\RefundRequest;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * Antrean pengajuan refund (D14).
 *
 * MITRA TIDAK TERLIBAT. Uang masuk ke rekening E-GOTO, jadi yang
 * mengembalikannya juga E-GOTO — tidak ada Resource kembar untuk model ini di
 * panel vendor, dan tidak boleh ditambahkan.
 *
 * Resource ini menjalankan kebijakan refund yang sudah tertulis di GUIDE.md,
 * bukan membuat kebijakan baru: tiga opsi, itu saja, dan tidak ada opsi
 * "hangus".
 */
class RefundRequestResource extends Resource
{
    use DibatasiScopeAdmin;

    public static function scopeAdmin(): AdminScope
    {
        return AdminScope::PaymentCs;
    }

    protected static ?string $model = RefundRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-refund';

    protected static ?string $navigationLabel = 'Refund';

    protected static ?string $modelLabel = 'pengajuan refund';

    protected static ?string $pluralModelLabel = 'pengajuan refund';

    protected static ?int $navigationSort = 2;

    /** Antrean yang menunggu tindakan, bukan total seumur hidup. */
    public static function getNavigationBadge(): ?string
    {
        $menunggu = static::getModel()::query()->berjalan()->count();

        return $menunggu > 0 ? (string) $menunggu : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        // Pengajuan tidak disunting bebas — perubahannya hanya lewat aksi
        // bertombol di bawah, supaya tiap perpindahan status melewati penjaga
        // yang sama di ProcessRefundRequest.
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateIcon('heroicon-o-receipt-refund')
            ->emptyStateHeading('Tidak ada pengajuan refund')
            ->emptyStateDescription('Antrean bersih. Pengajuan baru muncul di sini begitu customer mengirimnya.')
            ->defaultSort('created_at', 'asc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['booking.user', 'booking.schedule.trip', 'processedBy']))
            ->columns([
                Tables\Columns\TextColumn::make('booking.code')
                    ->label('Kode booking')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),
                Tables\Columns\TextColumn::make('booking.user.name')
                    ->label('Customer')
                    ->searchable()
                    ->description(fn (RefundRequest $record): ?string => $record->booking->user?->email),
                Tables\Columns\TextColumn::make('booking.schedule.trip.title')
                    ->label('Trip')
                    ->wrap()
                    ->limit(40),
                Tables\Columns\TextColumn::make('booking.total_amount')
                    ->label('Nominal dibayar')
                    ->money('IDR', locale: 'id'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Opsi')
                    ->badge()
                    ->formatStateUsing(fn (RefundType $state): string => $state->label()),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (RefundStatus $state): string => $state->label())
                    ->color(fn (RefundStatus $state): string => $state->tone()),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->dateTime('j M Y, H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('processedBy.name')
                    ->label('Diproses oleh')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(RefundStatus::cases())
                        ->mapWithKeys(fn (RefundStatus $status): array => [$status->value => $status->label()])
                        ->all()),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Opsi')
                    ->options(collect(RefundType::cases())
                        ->mapWithKeys(fn (RefundType $type): array => [$type->value => $type->label()])
                        ->all()),
            ])
            ->actions([
                Tables\Actions\Action::make('setujui')
                    ->label('Setujui')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Kursi peserta dilepas untuk opsi refund 100%. Uangnya ditandai terkirim di langkah terpisah.')
                    ->form([
                        Textarea::make('admin_note')->label('Catatan (opsional)')->rows(3),
                    ])
                    ->visible(fn (RefundRequest $record): bool => $record->status === RefundStatus::Diajukan)
                    ->action(fn (RefundRequest $record, array $data) => static::jalankan(
                        fn () => app(ProcessRefundRequest::class)->setujui($record, auth()->user(), $data['admin_note'] ?? null),
                        'Pengajuan disetujui.',
                    )),

                Tables\Actions\Action::make('tolak')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->form([
                        // Wajib. Alasan itu satu-satunya petunjuk customer untuk
                        // memperbaiki pengajuannya — sama dengan penolakan bukti
                        // bayar di D5.
                        Textarea::make('admin_note')->label('Alasan penolakan')->required()->rows(3),
                    ])
                    ->visible(fn (RefundRequest $record): bool => $record->status === RefundStatus::Diajukan)
                    ->action(fn (RefundRequest $record, array $data) => static::jalankan(
                        fn () => app(ProcessRefundRequest::class)->tolak($record, auth()->user(), $data['admin_note']),
                        'Pengajuan ditolak.',
                    )),

                Tables\Actions\Action::make('selesai')
                    ->label('Tandai selesai')
                    ->icon('heroicon-o-banknotes')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalDescription('Tekan setelah uangnya benar-benar terkirim, atau trip penggantinya sudah dibuatkan.')
                    ->visible(fn (RefundRequest $record): bool => $record->status === RefundStatus::Disetujui)
                    ->action(fn (RefundRequest $record) => static::jalankan(
                        fn () => app(ProcessRefundRequest::class)->tandaiSelesai($record, auth()->user()),
                        'Ditandai selesai.',
                    )),
            ]);
    }

    /**
     * Kegagalan penjaga di Action muncul sebagai notifikasi, bukan halaman
     * error 500 — dua admin yang menekan tombol yang sama nyaris bersamaan
     * adalah keadaan yang wajar, bukan kerusakan.
     */
    private static function jalankan(callable $aksi, string $pesanSukses): void
    {
        try {
            $aksi();
        } catch (ValidationException $e) {
            Notification::make()
                ->title('Tidak bisa diproses')
                ->body(collect($e->errors())->flatten()->first())
                ->danger()
                ->send();

            return;
        }

        Notification::make()->title($pesanSukses)->success()->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRefundRequests::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        // Pengajuan datang dari customer, bukan dibuat admin. Tombol "buat
        // baru" di sini akan membuat catatan yang tidak pernah customer minta.
        return false;
    }
}
