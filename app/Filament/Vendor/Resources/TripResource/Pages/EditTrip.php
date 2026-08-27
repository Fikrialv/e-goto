<?php

namespace App\Filament\Vendor\Resources\TripResource\Pages;

use App\Enums\TripStatus;
use App\Filament\Vendor\Resources\TripResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTrip extends EditRecord
{
    protected static string $resource = TripResource::class;

    /**
     * Mitra tidak boleh mengajukan trip kosong: tanpa jadwal, admin tidak punya
     * apa pun untuk ditinjau dan trip itu tetap tidak bisa dipesan walau lolos.
     */
    protected function beforeSave(): void
    {
        if (($this->data['status'] ?? null) !== TripStatus::PendingReview->value) {
            return;
        }

        if ($this->record->schedules()->exists()) {
            return;
        }

        Notification::make()
            ->danger()
            ->title('Belum bisa diajukan')
            ->body('Tambahkan minimal satu jadwal keberangkatan beserta harganya di tab "Jadwal & harga" sebelum mengajukan trip ini ke admin.')
            ->persistent()
            ->send();

        $this->halt();
    }
}
