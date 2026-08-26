<?php

namespace App\Filament\Resources\TripResource\Pages;

use App\Enums\TripStatus;
use App\Filament\Resources\TripResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTrip extends EditRecord
{
    protected static string $resource = TripResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Trip tanpa jadwal tidak boleh naik ke `published`.
     *
     * Kalau lolos, hasilnya membingungkan dari dua sisi sekaligus: halaman
     * detailnya terbuka untuk umum tapi bertuliskan "belum ada jadwal", dan di
     * halaman kategori trip itu tidak muncul sama sekali (daftar kategori
     * menyaring trip yang punya jadwal terbuka). Dihentikan di sini dengan
     * pesan yang menyebut langkah berikutnya, bukan galat mentah.
     */
    protected function beforeSave(): void
    {
        $status = $this->data['status'] ?? null;

        if ($status !== TripStatus::Published->value) {
            return;
        }

        if ($this->record->schedules()->exists()) {
            return;
        }

        Notification::make()
            ->danger()
            ->title('Trip belum bisa dipublikasikan')
            ->body('Tambahkan minimal satu jadwal keberangkatan di tab "Jadwal & harga" dulu. Tanpa jadwal, trip ini tidak akan muncul di halaman kategori dan tidak bisa dipesan.')
            ->persistent()
            ->send();

        $this->halt();
    }
}
