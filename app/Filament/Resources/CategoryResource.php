<?php

namespace App\Filament\Resources;

use App\Enums\IdType;
use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Kategori';

    protected static ?string $modelLabel = 'kategori';

    protected static ?string $pluralModelLabel = 'kategori';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', Str::slug($state))),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\Select::make('id_requirement')
                    ->label('Wajib identitas')
                    ->options([
                        IdType::None->value => 'Tanpa identitas',
                        IdType::Nik->value => 'NIK',
                        IdType::Passport->value => 'Paspor',
                    ])
                    ->required()
                    ->default(IdType::None->value),
                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Urutan tampil')
                    ->required()
                    ->numeric()
                    ->default(0),
                /*
                 * Daftar tertutup, bukan teks bebas: nilainya dirender langsung
                 * jadi komponen <x-lucide-*> di grid kategori homepage, dan nama
                 * ikon yang tidak ada akan melempar SvgNotFound di halaman publik.
                 */
                Forms\Components\Select::make('icon')
                    ->label('Ikon')
                    ->options(Category::ICON_OPTIONS)
                    ->native(false)
                    ->helperText('Tampil di grid kategori homepage. Kosongkan untuk memakai ikon kompas.'),
                Forms\Components\Repeater::make('gear_checklist')
                    ->label('Checklist perlengkapan')
                    ->simple(
                        Forms\Components\TextInput::make('item')
                            ->label('Barang bawaan')
                            ->required()
                    )
                    // Checklist opsional (kolom nullable) — jangan paksa admin
                    // menghapus baris kosong bawaan Filament tiap buka form baru.
                    ->defaultItems(0)
                    ->addActionLabel('Tambah barang')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateIcon('heroicon-o-rectangle-stack')
            ->emptyStateHeading('Belum ada kategori')
            ->emptyStateDescription('Tambahkan kategori dulu — trip selalu menempel ke salah satunya.')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('id_requirement')
                    ->label('Wajib identitas')
                    ->badge()
                    ->formatStateUsing(fn (IdType $state): string => match ($state) {
                        IdType::None => 'Tanpa identitas',
                        IdType::Nik => 'NIK',
                        IdType::Passport => 'Paspor',
                    }),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktif'),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('trips_count')
                    ->label('Jumlah trip')
                    ->counts('trips'),
            ])
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
