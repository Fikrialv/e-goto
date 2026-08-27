<?php

namespace App\Filament\Resources\TripResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Opsi aktivitas tambahan per trip (D10) — mis. Camping, Tubing, Playground.
 *
 * Harganya per orang dan ikut masuk `subtotal` saat checkout. Menonaktifkan
 * opsi (bukan menghapusnya) adalah cara yang benar untuk menghentikan penjualan:
 * booking lama menyimpan harga yang sudah dibekukan di `booking_options`, dan
 * menghapus barisnya akan memutus riwayat itu.
 */
class OptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'options';

    protected static ?string $title = 'Opsi tambahan';

    protected static ?string $modelLabel = 'opsi';

    protected static ?string $pluralModelLabel = 'opsi';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama opsi')
                    ->placeholder('Camping / Tubing / Playground')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('extra_price')
                    ->label('Harga tambahan per orang')
                    ->prefix('Rp')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                Forms\Components\TextInput::make('description')
                    ->label('Keterangan')
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Urutan tampil')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Dijual')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Opsi')->wrap(),
                Tables\Columns\TextColumn::make('extra_price')->label('Harga')->money('IDR', 0),
                Tables\Columns\IconColumn::make('is_active')->label('Dijual')->boolean(),
                Tables\Columns\TextColumn::make('sort_order')->label('Urutan')->numeric(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Tambah opsi'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
