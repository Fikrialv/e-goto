<?php

namespace App\Filament\Vendor\Pages;

use App\Models\Vendor;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Profil usaha mitra — sumber isi halaman publik `/mitra/{slug}`.
 *
 * Satu record, jadi Page bukan Resource: daftar berisi satu baris dan tombol
 * "buat baru" yang tidak boleh ditekan cuma membingungkan.
 *
 * Dua kolom sengaja TIDAK bisa disunting mitra: `slug` karena ia sudah jadi
 * alamat publik yang mungkin sudah dibagikan (mengubahnya diam-diam mematikan
 * tautan yang beredar), dan `status` karena persetujuan adalah keputusan admin
 * — mitra yang bisa menyetujui dirinya sendiri membuat seluruh tinjauan D8
 * tidak ada artinya.
 */
class ProfilUsaha extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Profil Usaha';

    protected static ?string $title = 'Profil usaha';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.vendor.pages.profil-usaha';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->profil()?->only([
            'business_name', 'description', 'logo', 'phone', 'address',
        ]) ?? []);
    }

    /**
     * Mitra yang akunnya belum punya baris `vendors` (misalnya dibuat manual di
     * database) tidak boleh melihat form yang tidak bisa disimpan.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->vendorProfile !== null;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Identitas usaha')
                    ->description('Ini yang dilihat calon peserta di halaman publik mitra.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('business_name')
                            ->label('Nama usaha')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label('Tentang usaha')
                            ->rows(4)
                            ->maxLength(1000)
                            ->helperText('Ceritakan singkat siapa kalian dan trip seperti apa yang biasa dibawa.')
                            ->columnSpanFull(),
                        FileUpload::make('logo')
                            ->label('Logo')
                            ->image()
                            ->disk('public')
                            ->directory('vendor-logos')
                            ->maxSize(2048)
                            ->helperText('PNG atau JPG, maksimal 2 MB. Kosongkan kalau belum punya.'),
                    ]),

                Section::make('Kontak')
                    ->columns(2)
                    ->schema([
                        TextInput::make('phone')
                            ->label('Telepon')
                            ->tel()
                            ->maxLength(30),
                        TextInput::make('address')
                            ->label('Basis operasi')
                            ->maxLength(255)
                            ->helperText('Kota atau daerah, bukan alamat lengkap — ini tampil publik.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function simpan(): void
    {
        $profil = $this->profil();

        abort_if($profil === null, 403);

        // Hanya kolom di form yang ditulis. `slug`, `status`, `approved_at`,
        // dan `commission_percent` tidak pernah ikut, walau dikirim manual ke
        // Livewire.
        $profil->update($this->form->getState());

        Notification::make()
            ->title('Profil usaha tersimpan')
            ->body('Perubahannya langsung tampil di halaman publik mitra.')
            ->success()
            ->send();
    }

    private function profil(): ?Vendor
    {
        return auth()->user()?->vendorProfile;
    }
}
