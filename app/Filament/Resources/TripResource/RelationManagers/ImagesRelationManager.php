<?php

namespace App\Filament\Resources\TripResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Galeri foto trip. Urutan tampil ditentukan `sort_order` — relasi
 * `Trip::images()` sudah mengurutkannya, jadi angka di sini langsung terlihat
 * hasilnya di halaman publik.
 */
class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $title = 'Galeri';

    protected static ?string $modelLabel = 'foto';

    protected static ?string $pluralModelLabel = 'foto';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Disk publik + path relatif, sama seperti cover_image: komponen
                // x-trip-image merendernya lewat Storage::url().
                Forms\Components\FileUpload::make('path')
                    ->label('Foto')
                    ->image()
                    ->disk('public')
                    ->directory('trips')
                    ->maxSize(4096)
                    ->required(),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Urutan tampil')
                    ->numeric()
                    ->default(0)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('path')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('path')
                    ->label('Foto')
                    ->disk('public'),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->numeric()
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Tambah foto'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
