<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Actions\VerifyPayment;
use App\Enums\PaymentStatus;
use App\Filament\Resources\PaymentResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;

/**
 * Layar verifikasi: bukti bayar besar, berdampingan dengan nominal seharusnya.
 *
 * Tata letaknya disengaja — tugas admin di sini adalah mencocokkan dua angka,
 * dan memaksanya berpindah halaman untuk melihat salah satunya adalah cara
 * paling mudah membuat pembayaran salah disetujui.
 */
class ReviewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    protected static string $view = 'filament.resources.payment-resource.pages.review-payment';

    public function getTitle(): string
    {
        return 'Periksa pembayaran '.$this->record->booking->code;
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        $belumDiputuskan = fn (): bool => $this->record->status === PaymentStatus::Pending;

        return [
            Action::make('setujui')
                ->label('Setujui & terbitkan tiket')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible($belumDiputuskan)
                ->requiresConfirmation()
                ->modalHeading('Setujui pembayaran ini?')
                ->modalDescription('Booking menjadi terkonfirmasi dan tiket peserta langsung terbit. Tindakan ini tidak bisa dibatalkan sendiri.')
                ->action(function (VerifyPayment $verifier): void {
                    $verifier->approve($this->record, auth()->user());

                    $this->record->refresh();

                    Notification::make()
                        ->success()
                        ->title('Pembayaran disetujui')
                        ->body('Tiket peserta sudah diterbitkan.')
                        ->send();
                }),

            Action::make('tolak')
                ->label('Tolak')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible($belumDiputuskan)
                ->form([
                    Textarea::make('reject_reason')
                        ->label('Alasan penolakan')
                        ->helperText('Ditampilkan apa adanya ke customer — tulis yang bisa ditindaklanjuti, misal "nominal kurang Rp123" atau "bukti tidak terbaca".')
                        ->required()
                        ->minLength(5)
                        ->maxLength(500)
                        ->rows(3),
                ])
                ->action(function (array $data, VerifyPayment $verifier): void {
                    try {
                        $verifier->reject($this->record, auth()->user(), $data['reject_reason']);
                    } catch (ValidationException $e) {
                        Notification::make()->danger()->title($e->getMessage())->send();

                        return;
                    }

                    $this->record->refresh();

                    Notification::make()
                        ->success()
                        ->title('Pembayaran ditolak')
                        ->body('Customer bisa mengunggah ulang bukti pembayaran.')
                        ->send();
                }),
        ];
    }
}
