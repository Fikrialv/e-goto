<?php

namespace App\Filament\Pages;

use App\Enums\AdminScope;
use App\Enums\UserRole;
use App\Filament\Concerns\DibatasiScopeAdmin;
use App\Models\Payment;
use App\Models\RefundRequest;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

/**
 * Riwayat transaksi + refund satu customer, dalam satu layar (D14 item 4).
 *
 * Gunanya sengit dan sempit: saat ada sengketa ("saya sudah transfer",
 * "refund saya belum masuk"), admin butuh melihat seluruh jejak uang orang itu
 * berdampingan. Mencarinya lewat dua daftar terpisah yang masing-masing
 * difilter manual adalah cara paling mudah melewatkan satu baris.
 *
 * Read-only. Tidak ada tombol yang mengubah apa pun di sini — keputusan tetap
 * diambil di antrean pembayaran (D5) dan antrean refund, supaya tiap perubahan
 * status melewati penjaga yang sama.
 */
class RiwayatCustomer extends Page implements HasForms
{
    use DibatasiScopeAdmin;
    use InteractsWithForms;

    public static function scopeAdmin(): AdminScope
    {
        return AdminScope::PaymentCs;
    }

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Riwayat Customer';

    protected static ?string $title = 'Riwayat transaksi customer';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.riwayat-customer';

    public ?int $customerId = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('customerId')
                ->label('Customer')
                ->placeholder('Cari nama atau email')
                ->searchable()
                // Dicari lewat query, bukan dengan memuat seluruh tabel user ke
                // memori lalu menyaringnya di PHP.
                ->getSearchResultsUsing(fn (string $search): array => User::query()
                    ->where('role', UserRole::Customer)
                    ->where(fn ($query) => $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"))
                    ->limit(25)
                    ->pluck('name', 'id')
                    ->all())
                ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name)
                ->live(),
        ]);
    }

    public function getCustomerProperty(): ?User
    {
        return $this->customerId === null ? null : User::find($this->customerId);
    }

    /** @return Collection<int, Payment> */
    public function getPembayaranProperty(): Collection
    {
        if ($this->customerId === null) {
            return collect();
        }

        return Payment::query()
            ->whereHas('booking', fn ($query) => $query->where('user_id', $this->customerId))
            ->with(['booking.schedule.trip', 'verifiedBy'])
            ->latest()
            ->get();
    }

    /** @return Collection<int, RefundRequest> */
    public function getRefundProperty(): Collection
    {
        if ($this->customerId === null) {
            return collect();
        }

        return RefundRequest::query()
            ->whereHas('booking', fn ($query) => $query->where('user_id', $this->customerId))
            ->with(['booking.schedule.trip', 'processedBy'])
            ->latest()
            ->get();
    }
}
