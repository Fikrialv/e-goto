<?php

namespace App\Filament\Resources;

use App\Enums\TripDifficulty;
use App\Enums\TripStatus;
use App\Filament\Resources\TripResource\Pages;
use App\Filament\Resources\TripResource\RelationManagers;
use App\Models\Trip;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TripResource extends Resource
{
    protected static ?string $model = Trip::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Trip';

    protected static ?string $modelLabel = 'trip';

    protected static ?string $pluralModelLabel = 'trip';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identitas')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Judul')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', Str::slug($state))),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        /*
                         * TODO (D8/D9): aktifkan kembali sebagai
                         * Select::make('vendor_id')->relationship('vendor', 'business_name')
                         * begitu tabel `vendors` ada (PLAN.md §4 V1.5).
                         *
                         * Disembunyikan di V1 — bukan sekadar opsional. Semua trip
                         * sekarang milik E-GOTO (vendor_id null), dan kolom ini
                         * nanti merujuk `vendors.id`, bukan `users.id`. Kalau admin
                         * sempat memilih user vendor demo dari sini, angkanya jadi
                         * rujukan salah begitu tabel `vendors` dibuat.
                         */
                        Forms\Components\Hidden::make('vendor_id')
                            ->default(null)
                            ->dehydrated(false),
                    ]),

                Forms\Components\Section::make('Isi halaman')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(4),
                        Forms\Components\Textarea::make('itinerary')
                            ->label('Rencana perjalanan')
                            ->rows(5),
                        Forms\Components\Textarea::make('includes')
                            ->label('Sudah termasuk')
                            ->rows(3),
                        Forms\Components\Textarea::make('excludes')
                            ->label('Belum termasuk')
                            ->rows(3),
                        Forms\Components\TextInput::make('meeting_point')
                            ->label('Titik kumpul')
                            ->maxLength(255),
                        // Disimpan sebagai path relatif di disk publik — komponen
                        // x-trip-image merender lewat Storage::url(), jadi URL
                        // penuh atau disk lain akan menghasilkan gambar rusak.
                        Forms\Components\FileUpload::make('cover_image')
                            ->label('Foto sampul')
                            ->image()
                            ->disk('public')
                            ->directory('trips')
                            ->maxSize(4096),
                    ]),

                Forms\Components\Section::make('Publikasi')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(collect(TripStatus::cases())
                                ->mapWithKeys(fn (TripStatus $status): array => [$status->value => Str::headline($status->value)])
                                ->all())
                            ->default(TripStatus::Draft->value)
                            ->rule(Rule::enum(TripStatus::class))
                            ->required(),
                        Forms\Components\Select::make('difficulty_level')
                            ->label('Tingkat kesulitan')
                            ->options(collect(TripDifficulty::cases())
                                ->mapWithKeys(fn (TripDifficulty $level): array => [$level->value => $level->label()])
                                ->all())
                            ->placeholder('Tidak relevan untuk trip ini')
                            ->helperText('Dipakai filter level fisik di halaman kategori.'),
                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Tanggal publikasi'),
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Tampilkan di beranda'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (TripStatus $state): string => Str::headline($state->value)),
                Tables\Columns\TextColumn::make('difficulty_level')
                    ->label('Level')
                    ->formatStateUsing(fn (?TripDifficulty $state): string => $state?->label() ?? '—'),
                Tables\Columns\TextColumn::make('schedules_count')
                    ->label('Jadwal')
                    ->counts('schedules'),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Beranda')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(TripStatus::cases())
                        ->mapWithKeys(fn (TripStatus $status): array => [$status->value => Str::headline($status->value)])
                        ->all()),
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),
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
            RelationManagers\SchedulesRelationManager::class,
            RelationManagers\ImagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrips::route('/'),
            'create' => Pages\CreateTrip::route('/create'),
            'edit' => Pages\EditTrip::route('/{record}/edit'),
        ];
    }
}
