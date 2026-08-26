<?php

namespace App\Filament\Resources\TripResource\RelationManagers;

use App\Enums\TripStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Jadwal keberangkatan beserta tingkat harganya.
 *
 * Harga bersarang di form jadwal lewat `Repeater::relationship()`, bukan
 * relation manager sendiri: satu tingkat harga tidak punya arti lepas dari
 * jadwal induknya, dan memisahkannya memaksa admin pindah layar dua kali untuk
 * satu keberangkatan.
 */
class SchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'schedules';

    protected static ?string $title = 'Jadwal & harga';

    protected static ?string $modelLabel = 'jadwal';

    protected static ?string $pluralModelLabel = 'jadwal';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('start_date')
                    ->label('Tanggal berangkat')
                    ->required(),
                Forms\Components\DatePicker::make('end_date')
                    ->label('Tanggal pulang')
                    ->afterOrEqual('start_date'),
                Forms\Components\TextInput::make('quota')
                    ->label('Kuota kursi')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                /*
                 * Sengaja read-only. Angka ini dikunci `lockForUpdate()` saat
                 * booking dibuat (PLAN.md §5); mengetiknya manual di sini akan
                 * membuat kursi terjual dua kali tanpa jejak.
                 */
                Forms\Components\TextInput::make('booked_count')
                    ->label('Kursi terisi')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Terisi otomatis dari booking — tidak bisa disunting di sini.'),
                // Enum, bukan teks bebas (CLAUDE.md §4): salah ketik status di
                // sini akan menghilang diam-diam, tidak ada yang menangkapnya.
                Forms\Components\Select::make('status')
                    ->label('Status jadwal')
                    ->options(collect(TripStatus::cases())
                        ->mapWithKeys(fn (TripStatus $status): array => [$status->value => Str::headline($status->value)])
                        ->all())
                    ->default(TripStatus::Published->value)
                    // Daftar opsi saja tidak menolak nilai asing yang dikirim
                    // langsung ke Livewire — cast Enum di model akan meledak
                    // saat baris dibaca, jauh dari tempat asalnya.
                    ->rule(Rule::enum(TripStatus::class))
                    ->required(),
                Forms\Components\Repeater::make('prices')
                    ->label('Tingkat harga')
                    ->relationship()
                    ->columns(2)
                    // Jadwal tanpa tingkat harga tidak bisa dipesan, jadi minimal
                    // satu baris wajib diisi.
                    ->minItems(1)
                    ->defaultItems(0)
                    ->addActionLabel('Tambah tingkat harga')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('label')
                            ->label('Nama tingkat')
                            ->placeholder('Reguler / Rombongan 5+')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('price')
                            ->label('Harga per orang')
                            ->prefix('Rp')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                        // Wajib walau punya default: kolomnya NOT NULL, dan field
                        // yang dikosongkan saat menyunting akan mengirim null —
                        // galat database, bukan pesan validasi yang bisa dibaca.
                        Forms\Components\TextInput::make('min_pax')
                            ->label('Minimal peserta')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required(),
                        Forms\Components\TextInput::make('max_pax')
                            ->label('Maksimal peserta')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Kosongkan kalau tidak dibatasi.'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('start_date')
            ->defaultSort('start_date')
            ->columns([
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Berangkat')
                    ->date('j M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Pulang')
                    ->date('j M Y')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('quota')
                    ->label('Kuota')
                    ->numeric(),
                Tables\Columns\TextColumn::make('booked_count')
                    ->label('Terisi')
                    ->numeric(),
                Tables\Columns\TextColumn::make('prices_count')
                    ->label('Tingkat harga')
                    ->counts('prices'),
                Tables\Columns\TextColumn::make('prices_min_price')
                    ->label('Mulai dari')
                    ->min('prices', 'price')
                    ->money('IDR', 0),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Tambah jadwal'),
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
