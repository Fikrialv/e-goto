<?php

namespace App\Filament\Vendor\Resources\TripResource\Pages;

use App\Filament\Vendor\Resources\TripResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTrips extends ListRecords
{
    protected static string $resource = TripResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Ajukan trip baru'),
        ];
    }
}
