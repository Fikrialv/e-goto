<?php

namespace App\Filament\Vendor\Resources\TripResource\Pages;

use App\Filament\Vendor\Resources\TripResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTrip extends CreateRecord
{
    protected static string $resource = TripResource::class;

    /**
     * Pemilik trip diambil dari sesi, bukan dari form — kalau `vendor_id` ikut
     * dikirim dari sisi klien, mitra bisa menitipkan trip atas nama mitra lain.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['vendor_id'] = auth()->id();

        return $data;
    }
}
