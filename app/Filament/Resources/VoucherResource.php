<?php

namespace App\Filament\Resources;

use App\Enums\AdminScope;
use App\Enums\VoucherScope;
use App\Enums\VoucherType;
use App\Filament\Concerns\DibatasiScopeAdmin;
use App\Filament\Resources\VoucherResource\Pages;
use App\Models\Category;
use App\Models\Trip;
use App\Models\Voucher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Voucher promo (D10).
 *
 * `used_count` sengaja tidak bisa disunting: angka itu bertambah di dalam
 * transaksi checkout bersama booking-nya, dan mengetiknya manual di sini akan
 * membuat kuota promo berbeda dari pemakaian sebenarnya.
 */
class VoucherResource extends Resource
{
    use DibatasiScopeAdmin;

    public static function scopeAdmin(): AdminScope
    {
        return AdminScope::TripManager;
    }

    protected static ?string $model = Voucher::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'Voucher';

    protected static ?string $modelLabel = 'voucher';

    protected static ?string $pluralModelLabel = 'voucher';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Kode & potongan')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Kode')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            // Disimpan huruf besar supaya pencocokan di checkout
                            // tidak bergantung cara customer mengetiknya.
                            ->dehydrateStateUsing(fn (string $state): string => Str::upper(trim($state)))
                            ->helperText('Contoh: LIBURAN25. Disimpan huruf besar.'),
                        Forms\Components\Select::make('type')
                            ->label('Bentuk potongan')
                            ->options(collect(VoucherType::cases())
                                ->mapWithKeys(fn (VoucherType $type): array => [$type->value => $type->label()])
                                ->all())
                            ->default(VoucherType::Percent->value)
                            ->rule(Rule::enum(VoucherType::class))
                            ->required()
                            ->live(),
                        Forms\Components\TextInput::make('value')
                            ->label(fn (Forms\Get $get): string => $get('type') === VoucherType::Fixed->value
                                ? 'Potongan (Rp)'
                                : 'Potongan (%)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(fn (Forms\Get $get): int => $get('type') === VoucherType::Fixed->value
                                ? 100_000_000
                                : 100)
                            ->required(),
                        Forms\Components\TextInput::make('min_spend')
                            ->label('Minimal belanja (Rp)')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Kosongkan kalau tanpa syarat.'),
                    ]),

                Forms\Components\Section::make('Kuota & masa berlaku')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('quota')
                            ->label('Kuota pemakaian')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Kosongkan kalau tidak dibatasi.'),
                        Forms\Components\Placeholder::make('used_count')
                            ->label('Sudah dipakai')
                            ->content(fn (?Voucher $record): string => (string) ($record?->used_count ?? 0)),
                        Forms\Components\DateTimePicker::make('valid_from')->label('Mulai berlaku'),
                        Forms\Components\DateTimePicker::make('valid_until')->label('Berakhir'),
                        Forms\Components\Toggle::make('is_active')->label('Aktif')->default(true),
                    ]),

                Forms\Components\Section::make('Cakupan')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('scope')
                            ->label('Berlaku untuk')
                            ->options(collect(VoucherScope::cases())
                                ->mapWithKeys(fn (VoucherScope $scope): array => [$scope->value => $scope->label()])
                                ->all())
                            ->default(VoucherScope::Global->value)
                            ->rule(Rule::enum(VoucherScope::class))
                            ->required()
                            ->live(),
                        Forms\Components\Select::make('scope_id')
                            ->label(fn (Forms\Get $get): string => $get('scope') === VoucherScope::Trip->value ? 'Trip' : 'Kategori')
                            ->options(fn (Forms\Get $get): array => match ($get('scope')) {
                                VoucherScope::Trip->value => Trip::query()->orderBy('title')->pluck('title', 'id')->all(),
                                VoucherScope::Category->value => Category::query()->orderBy('name')->pluck('name', 'id')->all(),
                                default => [],
                            })
                            ->searchable()
                            ->visible(fn (Forms\Get $get): bool => $get('scope') !== VoucherScope::Global->value)
                            ->required(fn (Forms\Get $get): bool => $get('scope') !== VoucherScope::Global->value),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateIcon('heroicon-o-ticket')
            ->emptyStateHeading('Belum ada voucher')
            ->emptyStateDescription('Buat voucher untuk promo tanggal tertentu atau kategori tertentu.')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Kode')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Potongan')
                    ->formatStateUsing(fn (VoucherType $state, Voucher $record): string => $state === VoucherType::Percent
                        ? $record->value.'%'
                        : 'Rp'.number_format($record->value, 0, ',', '.')),
                Tables\Columns\TextColumn::make('scope')
                    ->label('Cakupan')
                    ->badge()
                    ->formatStateUsing(fn (VoucherScope $state): string => $state->label()),
                Tables\Columns\TextColumn::make('used_count')
                    ->label('Terpakai')
                    ->formatStateUsing(fn (int $state, Voucher $record): string => $record->quota === null
                        ? (string) $state
                        : $state.' / '.$record->quota),
                Tables\Columns\TextColumn::make('valid_until')
                    ->label('Berakhir')
                    ->dateTime('j M Y')
                    ->placeholder('tanpa batas'),
                Tables\Columns\IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Status aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVouchers::route('/'),
            'create' => Pages\CreateVoucher::route('/create'),
            'edit' => Pages\EditVoucher::route('/{record}/edit'),
        ];
    }
}
