<?php

namespace App\Filament\Vendor\Resources;

use App\Enums\TripDifficulty;
use App\Enums\TripStatus;
use App\Filament\Resources\TripResource\RelationManagers as AdminRelationManagers;
use App\Filament\Vendor\Resources\TripResource\Pages;
use App\Models\Trip;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Trip milik mitra (D9).
 *
 * Dua batas yang menentukan keamanannya, dan keduanya tidak boleh dilonggarkan:
 * query selalu disaring ke `vendor_id` milik user yang sedang masuk, dan mitra
 * hanya boleh memindahkan status antara `draft` dan `pending_review`. Naik ke
 * `published` adalah keputusan admin — kalau mitra bisa melakukannya sendiri,
 * review sebelum tayang tidak ada artinya.
 */
class TripResource extends Resource
{
    protected static ?string $model = Trip::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Trip Saya';

    protected static ?string $modelLabel = 'trip';

    protected static ?string $pluralModelLabel = 'trip';

    protected static ?int $navigationSort = 2;

    /**
     * Penjaga utama. Berlaku untuk daftar, sunting, dan hapus sekaligus —
     * termasuk saat id trip mitra lain diketik langsung di URL.
     *
     * @return Builder<Trip>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('vendor_id', auth()->id());
    }

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
                    ]),

                Forms\Components\Section::make('Isi halaman')
                    ->schema([
                        Forms\Components\Textarea::make('description')->label('Deskripsi')->rows(4),
                        Forms\Components\Textarea::make('itinerary')->label('Rencana perjalanan')->rows(5),
                        Forms\Components\Textarea::make('includes')->label('Sudah termasuk')->rows(3),
                        Forms\Components\Textarea::make('excludes')->label('Belum termasuk')->rows(3),
                        Forms\Components\TextInput::make('meeting_point')->label('Titik kumpul')->maxLength(255),
                        Forms\Components\FileUpload::make('cover_image')
                            ->label('Foto sampul')
                            ->image()
                            ->disk('public')
                            ->directory('trips')
                            ->maxSize(4096),
                    ]),

                Forms\Components\Section::make('Pengajuan')
                    ->columns(2)
                    ->schema([
                        // Hanya dua status. `published`/`rejected`/`archived`
                        // sengaja tidak ada di daftar ini.
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                TripStatus::Draft->value => 'Draf (belum diajukan)',
                                TripStatus::PendingReview->value => 'Ajukan untuk ditinjau admin',
                            ])
                            ->default(TripStatus::Draft->value)
                            // Daftar opsi tidak menolak nilai yang dikirim
                            // langsung ke Livewire — tanpa aturan ini, mitra bisa
                            // menayangkan tripnya sendiri lewat request buatan.
                            ->rule(Rule::in([TripStatus::Draft->value, TripStatus::PendingReview->value]))
                            ->required()
                            ->helperText('Trip tayang setelah admin menyetujui pengajuan Anda.'),
                        Forms\Components\Select::make('difficulty_level')
                            ->label('Tingkat kesulitan')
                            ->options(collect(TripDifficulty::cases())
                                ->mapWithKeys(fn (TripDifficulty $level): array => [$level->value => $level->label()])
                                ->all())
                            ->placeholder('Tidak relevan untuk trip ini'),
                        Forms\Components\Placeholder::make('review_note')
                            ->label('Catatan admin')
                            ->columnSpanFull()
                            ->visible(fn (?Trip $record): bool => filled($record?->review_note))
                            ->content(fn (?Trip $record): string => (string) $record?->review_note),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateIcon('heroicon-o-map')
            ->emptyStateHeading('Belum ada trip')
            ->emptyStateDescription('Ajukan trip baru — admin meninjau sebelum tayang.')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('category.name')->label('Kategori'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (TripStatus $state): string => match ($state) {
                        TripStatus::Draft => 'Draf',
                        TripStatus::PendingReview => 'Menunggu tinjauan',
                        TripStatus::Published => 'Tayang',
                        TripStatus::Rejected => 'Ditolak',
                        TripStatus::Archived => 'Diarsipkan',
                    })
                    ->color(fn (TripStatus $state): string => match ($state) {
                        TripStatus::Published => 'success',
                        TripStatus::Rejected => 'danger',
                        TripStatus::PendingReview => 'warning',
                        default => 'gray',
                    })
                    ->description(fn (Trip $record): ?string => $record->status === TripStatus::Rejected
                        ? Str::limit($record->review_note, 80)
                        : null),
                Tables\Columns\TextColumn::make('schedules_count')
                    ->label('Jadwal')
                    ->counts('schedules'),
                // Jumlah peserta dijumlahkan lewat subquery, bukan dihitung
                // per baris di dalam loop (CLAUDE.md §9).
                Tables\Columns\TextColumn::make('peserta')
                    ->label('Peserta')
                    ->state(fn (Trip $record): int => (int) $record->peserta)
                    ->badge(),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->withSum('schedules as peserta', 'booked_count'))
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(TripStatus::cases())
                        ->mapWithKeys(fn (TripStatus $status): array => [$status->value => Str::headline($status->value)])
                        ->all()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Trip yang sudah tayang tidak boleh dihapus mitra: bisa saja
                // sudah ada booking yang menggantung di jadwalnya.
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Trip $record): bool => in_array($record->status, [TripStatus::Draft, TripStatus::Rejected], true)),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AdminRelationManagers\SchedulesRelationManager::class,
            AdminRelationManagers\OptionsRelationManager::class,
            AdminRelationManagers\ImagesRelationManager::class,
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
