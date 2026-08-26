<?php

namespace App\Filament\Resources\TripResource\Pages;

use App\Enums\TripStatus;
use App\Filament\Resources\TripResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateTrip extends CreateRecord
{
    protected static string $resource = TripResource::class;

    /**
     * Trip baru tidak bisa langsung `published`.
     *
     * Jadwal & harga baru bisa diisi setelah tripnya tersimpan (relation
     * manager butuh record induk), jadi trip yang dipublikasikan di layar ini
     * dijamin belum punya jadwal — persis kondisi yang ditolak `EditTrip`.
     * Alurnya: simpan sebagai draf, isi jadwal, baru ubah statusnya.
     */
    protected function beforeCreate(): void
    {
        if (($this->data['status'] ?? null) !== TripStatus::Published->value) {
            return;
        }

        Notification::make()
            ->danger()
            ->title('Simpan sebagai draf dulu')
            ->body('Jadwal keberangkatan baru bisa diisi setelah trip tersimpan. Simpan sebagai draf, tambahkan minimal satu jadwal di tab "Jadwal & harga", lalu ubah statusnya jadi published.')
            ->persistent()
            ->send();

        $this->halt();
    }
}
