<?php

namespace App\Filament\Resources;

use App\Enums\AdminScope;
use App\Enums\TripDifficulty;
use App\Enums\TripStatus;
use App\Filament\Concerns\DibatasiScopeAdmin;
use App\Filament\Resources\TripResource\Pages;
use App\Filament\Resources\TripResource\RelationManagers;
use App\Models\Trip;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TripResource extends Resource
{
    use DibatasiScopeAdmin;

    public static function scopeAdmin(): AdminScope
    {
        return AdminScope::TripManager;
    }

    protected static ?string $model = Trip::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Trip';

    protected static ?string $modelLabel = 'trip';

    protected static ?string $pluralModelLabel = 'trip';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $menunggu = static::getModel()::where('status', TripStatus::PendingReview)->count();

        return $menunggu > 0 ? (string) $menunggu : null;
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
                        /*
                         * Sengaja tidak bisa diisi admin dari layar ini.
                         *
                         * Sejak D9, `trips.vendor_id` diisi dari sesi mitra yang
                         * membuat tripnya (`users.id`, lihat
                         * Vendor\Resources\TripResource\Pages\CreateTrip).
                         * Kepemilikan trip mengikuti siapa yang mengajukan, bukan
                         * dipindah-pindah lewat dropdown — memindahkannya diam-diam
                         * akan mengubah trip siapa yang muncul di panel mitra dan
                         * siapa yang melihat data pesertanya.
                         *
                         * Kalau nanti perlu pemindahan kepemilikan, buat aksi
                         * tersendiri yang tercatat, bukan field bebas di form.
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
            ->emptyStateIcon('heroicon-o-map')
            ->emptyStateHeading('Belum ada trip')
            ->emptyStateDescription('Buat trip baru, isi jadwalnya, lalu publikasikan.')
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
                /*
                 * Tinjauan pengajuan trip mitra (D9). Approve hanya boleh kalau
                 * jadwalnya sudah ada — penjaga yang sama dengan EditTrip,
                 * karena trip tayang tanpa jadwal tidak muncul di kategori dan
                 * tidak bisa dipesan.
                 */
                Tables\Actions\Action::make('setujui')
                    ->label('Setujui')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Trip $record): bool => $record->status === TripStatus::PendingReview)
                    ->action(function (Trip $record): void {
                        if (! $record->schedules()->exists()) {
                            Notification::make()
                                ->danger()
                                ->title('Trip belum punya jadwal')
                                ->body('Minta mitra menambahkan jadwal keberangkatan dulu — trip tanpa jadwal tidak muncul di halaman kategori dan tidak bisa dipesan.')
                                ->persistent()
                                ->send();

                            return;
                        }

                        $record->update([
                            'status' => TripStatus::Published,
                            'published_at' => $record->published_at ?? now(),
                            'review_note' => null,
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);

                        Notification::make()->success()->title('Trip tayang')->send();
                    }),

                Tables\Actions\Action::make('tolak')
                    ->label('Tolak')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->visible(fn (Trip $record): bool => $record->status === TripStatus::PendingReview)
                    ->form([
                        // Alasan wajib, sama seperti penolakan pembayaran di D5:
                        // mitra perlu tahu apa yang harus diperbaiki.
                        Forms\Components\Textarea::make('review_note')
                            ->label('Alasan penolakan')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Trip $record, array $data): void {
                        $record->update([
                            'status' => TripStatus::Rejected,
                            'review_note' => $data['review_note'],
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);

                        Notification::make()->danger()->title('Pengajuan trip ditolak')->send();
                    }),

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
            RelationManagers\OptionsRelationManager::class,
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
