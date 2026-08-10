<?php

namespace App\Filament\Vendor\Pages;

use App\Actions\CheckInTicket;
use App\Models\Ticket;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;

/**
 * Layar check-in lapangan.
 *
 * Petugas mengetik atau memindai token dari QR peserta. Hasilnya sengaja
 * ditampilkan besar dan dibedakan tegas antara "valid", "sudah dipakai", dan
 * "tidak valid" — di titik kumpul yang ramai, pesan yang samar membuat petugas
 * meloloskan tiket yang seharusnya ditolak.
 */
class CheckIn extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationLabel = 'Check-in Peserta';

    protected static ?string $title = 'Check-in Peserta';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.vendor.pages.check-in';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public ?Ticket $terakhir = null;

    public ?string $galat = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('token')
                    ->label('Token tiket')
                    ->placeholder('Tempel hasil pindai QR atau ketik token peserta')
                    ->helperText('32 karakter. Pemindai QR biasanya langsung mengisikan token dan menekan Enter.')
                    ->required()
                    ->autofocus()
                    ->autocomplete(false),
            ])
            ->statePath('data');
    }

    public function checkIn(CheckInTicket $action): void
    {
        $this->terakhir = null;
        $this->galat = null;

        $token = (string) ($this->form->getState()['token'] ?? '');

        try {
            $this->terakhir = $action->handle($token, auth()->user());
        } catch (ValidationException $e) {
            $this->galat = $e->validator->errors()->first('token');

            Notification::make()->danger()->title('Check-in ditolak')->body($this->galat)->send();

            $this->form->fill();

            return;
        }

        Notification::make()
            ->success()
            ->title('Check-in berhasil')
            ->body($this->terakhir->participant->full_name.' sudah masuk.')
            ->send();

        // Dikosongkan supaya petugas bisa langsung memindai peserta berikutnya
        // tanpa menghapus isian sebelumnya.
        $this->form->fill();
    }
}
