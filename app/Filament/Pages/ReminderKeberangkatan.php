<?php

namespace App\Filament\Pages;

use App\Contracts\MessagingService;
use App\Enums\BookingStatus;
use App\Models\Booking;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Antrean pengingat H-1 (D7.6 item d).
 *
 * Tanpa cron dan tanpa queue: server tidak bisa mengirim WhatsApp sendiri lewat
 * `wa.me`, jadi admin yang menekan tombolnya per baris. Konsisten dengan D5 dan
 * dengan "tidak ada worker daemon di shared hosting".
 */
class ReminderKeberangkatan extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationLabel = 'Pengingat H-1';

    protected static ?string $title = 'Pengingat keberangkatan besok';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.reminder-keberangkatan';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->antrean())
            ->defaultSort('bookings.code')
            ->emptyStateHeading('Tidak ada keberangkatan besok')
            ->emptyStateDescription('Antrean ini hanya memuat booking terkonfirmasi yang berangkat besok.')
            ->columns([
                TextColumn::make('code')
                    ->label('Kode booking')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('schedule.trip.title')
                    ->label('Trip')
                    ->wrap(),
                TextColumn::make('schedule.start_date')
                    ->label('Berangkat')
                    ->date('j M Y'),
                TextColumn::make('pax_count')
                    ->label('Peserta')
                    ->numeric(),
                TextColumn::make('user.name')
                    ->label('Pemesan')
                    ->description(fn (Booking $record): string => $record->user?->phone ?? 'nomor belum diisi'),
            ])
            ->actions([
                Action::make('ingatkan')
                    ->label('Kirim pengingat')
                    ->icon('heroicon-m-chat-bubble-left-right')
                    ->url(fn (Booking $record): string => app(MessagingService::class)->remindDayBefore($record))
                    ->openUrlInNewTab(),
            ]);
    }

    /**
     * Booking terkonfirmasi yang jadwalnya berangkat besok.
     *
     * Perbandingan lewat `whereDate` pada jadwal, bukan rentang waktu di PHP —
     * `start_date` bertipe date, jadi "besok" di sini berarti tanggalnya, bukan
     * 24 jam ke depan yang akan ikut menyeret keberangkatan lusa dini hari.
     *
     * @return Builder<Booking>
     */
    private function antrean(): Builder
    {
        return Booking::query()
            ->where('status', BookingStatus::Confirmed)
            ->whereHas('schedule', fn (Builder $query) => $query->whereDate('start_date', today()->addDay()))
            ->with(['user', 'schedule.trip.category', 'participants']);
    }
}
