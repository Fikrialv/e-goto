<?php

namespace App\Filament\Resources;

use App\Actions\ApproveVendorApplication;
use App\Enums\VendorStatus;
use App\Filament\Resources\VendorApplicationResource\Pages;
use App\Models\VendorApplication;
use Filament\Forms;
use Filament\Infolists\Components\Component;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * Antrean pengajuan mitra (D8).
 *
 * Tidak ada form create/edit: barisnya lahir dari form publik `/jadi-mitra`,
 * dan yang dilakukan admin di sini cuma tiga hal — menjadwalkan obrolan,
 * menyetujui, atau menolak dengan alasan.
 */
class VendorApplicationResource extends Resource
{
    protected static ?string $model = VendorApplication::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Pengajuan Mitra';

    protected static ?string $modelLabel = 'pengajuan mitra';

    protected static ?string $pluralModelLabel = 'pengajuan mitra';

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        $menunggu = static::getModel()::where('status', VendorStatus::Pending)->count();

        return $menunggu > 0 ? (string) $menunggu : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateIcon('heroicon-o-inbox-arrow-down')
            ->emptyStateHeading('Belum ada pengajuan mitra')
            ->emptyStateDescription('Pengajuan dari halaman /jadi-mitra akan masuk ke sini.')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('business_name')
                    ->label('Usaha')
                    ->searchable()
                    ->wrap()
                    ->description(fn (VendorApplication $record): string => $record->contact_name.' · '.$record->contact_phone),
                Tables\Columns\TextColumn::make('contact_email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (VendorStatus $state): string => Str::headline($state->value))
                    ->color(fn (VendorStatus $state): string => match ($state) {
                        VendorStatus::Approved => 'success',
                        VendorStatus::Rejected => 'danger',
                        VendorStatus::Suspended => 'warning',
                        VendorStatus::Pending => 'gray',
                    }),
                Tables\Columns\TextColumn::make('meeting_at')
                    ->label('Jadwal ngobrol')
                    ->dateTime('j M Y, H:i')
                    ->placeholder('belum dijadwalkan'),
                Tables\Columns\TextColumn::make('documents')
                    ->label('Dokumen')
                    ->formatStateUsing(fn (?array $state): string => count($state ?? []).' berkas'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Masuk')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(VendorStatus::cases())
                        ->mapWithKeys(fn (VendorStatus $status): array => [$status->value => Str::headline($status->value)])
                        ->all())
                    ->default(VendorStatus::Pending->value),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->modalHeading('Detail pengajuan')
                    ->infolist(fn (): array => static::detail()),

                Tables\Actions\Action::make('jadwalkan')
                    ->label('Jadwalkan')
                    ->icon('heroicon-m-calendar-days')
                    ->visible(fn (VendorApplication $record): bool => $record->status === VendorStatus::Pending)
                    ->form([
                        Forms\Components\DateTimePicker::make('meeting_at')
                            ->label('Waktu ngobrol')
                            ->required(),
                        Forms\Components\Textarea::make('admin_note')
                            ->label('Catatan')
                            ->rows(3),
                    ])
                    ->action(function (VendorApplication $record, array $data): void {
                        $record->update([
                            'meeting_at' => $data['meeting_at'],
                            'admin_note' => $data['admin_note'] ?? $record->admin_note,
                        ]);

                        Notification::make()->success()->title('Jadwal disimpan')->send();
                    }),

                Tables\Actions\Action::make('setujui')
                    ->label('Setujui')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Akun panel mitra dibuat dari email pengajuan ini. Password sementara ditampilkan sekali — salin dan kirimkan ke mitra lewat WhatsApp.')
                    ->visible(fn (VendorApplication $record): bool => $record->status === VendorStatus::Pending)
                    ->action(function (VendorApplication $record, ApproveVendorApplication $approve): void {
                        $hasil = $approve->handle($record, auth()->user());

                        Notification::make()
                            ->success()
                            ->title('Mitra disetujui')
                            ->body($hasil['password'] === null
                                ? 'Akun '.$hasil['user']->email.' sudah ada sebelumnya dan sekarang berperan vendor. Passwordnya tidak diubah.'
                                : 'Akun: '.$hasil['user']->email.' · password sementara: '.$hasil['password'].' — minta mitra menggantinya setelah masuk.')
                            ->persistent()
                            ->send();
                    }),

                Tables\Actions\Action::make('tolak')
                    ->label('Tolak')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->visible(fn (VendorApplication $record): bool => $record->status === VendorStatus::Pending)
                    ->form([
                        // Alasan wajib: pengaju berhak tahu apa yang kurang, dan
                        // tanpa itu keputusan lama tidak bisa ditelusuri ulang.
                        Forms\Components\Textarea::make('admin_note')
                            ->label('Alasan penolakan')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (VendorApplication $record, array $data): void {
                        $record->update([
                            'status' => VendorStatus::Rejected,
                            'admin_note' => $data['admin_note'],
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);

                        Notification::make()->danger()->title('Pengajuan ditolak')->send();
                    }),
            ]);
    }

    /**
     * @return array<int, Component>
     */
    protected static function detail(): array
    {
        return [
            TextEntry::make('business_name')->label('Nama usaha'),
            TextEntry::make('contact_name')->label('Penanggung jawab'),
            TextEntry::make('contact_phone')->label('WhatsApp'),
            TextEntry::make('contact_email')->label('Email'),
            TextEntry::make('experience')
                ->label('Pengalaman')
                ->placeholder('tidak diisi')
                ->columnSpanFull(),
            TextEntry::make('admin_note')
                ->label('Catatan admin')
                ->placeholder('belum ada')
                ->columnSpanFull(),
            ViewEntry::make('documents')
                ->label('Dokumen')
                ->view('filament.resources.vendor-application-resource.documents')
                ->columnSpanFull(),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendorApplications::route('/'),
        ];
    }
}
