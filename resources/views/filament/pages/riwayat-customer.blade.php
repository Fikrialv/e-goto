<x-filament-panels::page>
    <p class="text-sm text-gray-500 dark:text-gray-400">
        Read-only. Untuk memutuskan sesuatu, buka antrean Pembayaran atau antrean Refund —
        di sana keputusannya tercatat beserta siapa yang mengambilnya.
    </p>

    {{ $this->form }}

    @if ($this->customer)
        <x-filament::section :heading="'Pembayaran — ' . $this->customer->name">
            @if ($this->pembayaran->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada pembayaran tercatat.</p>
            @else
                <table class="w-full text-sm">
                    <thead class="text-left text-xs uppercase text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="pb-3 pr-4 font-medium">Kode booking</th>
                            <th class="pb-3 pr-4 font-medium">Trip</th>
                            <th class="pb-3 pr-4 font-medium">Nominal</th>
                            <th class="pb-3 pr-4 font-medium">Status</th>
                            <th class="pb-3 font-medium">Waktu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @foreach ($this->pembayaran as $bayar)
                            <tr>
                                <td class="py-3 pr-4 font-mono">{{ $bayar->booking->code }}</td>
                                <td class="py-3 pr-4">{{ Str::limit($bayar->booking->schedule->trip->title, 40) }}</td>
                                <td class="py-3 pr-4">Rp{{ number_format($bayar->amount_declared, 0, ',', '.') }}</td>
                                <td class="py-3 pr-4">
                                    <x-filament::badge :color="match ($bayar->status) {
                                        App\Enums\PaymentStatus::Verified => 'success',
                                        App\Enums\PaymentStatus::Pending => 'warning',
                                        App\Enums\PaymentStatus::Rejected => 'danger',
                                    }">
                                        {{ Str::headline($bayar->status->value) }}
                                    </x-filament::badge>

                                    @if ($bayar->is_duplicate_flagged)
                                        <x-filament::badge color="danger">Duplikat</x-filament::badge>
                                    @endif
                                </td>
                                <td class="py-3 whitespace-nowrap">{{ $bayar->created_at->format('j M Y, H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-filament::section>

        <x-filament::section heading="Pengajuan refund">
            @if ($this->refund->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada pengajuan refund.</p>
            @else
                <table class="w-full text-sm">
                    <thead class="text-left text-xs uppercase text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="pb-3 pr-4 font-medium">Kode booking</th>
                            <th class="pb-3 pr-4 font-medium">Opsi</th>
                            <th class="pb-3 pr-4 font-medium">Status</th>
                            <th class="pb-3 pr-4 font-medium">Diproses oleh</th>
                            <th class="pb-3 font-medium">Diajukan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @foreach ($this->refund as $ajuan)
                            <tr>
                                <td class="py-3 pr-4 font-mono">{{ $ajuan->booking->code }}</td>
                                <td class="py-3 pr-4">{{ $ajuan->type->label() }}</td>
                                <td class="py-3 pr-4">
                                    <x-filament::badge :color="$ajuan->status->tone()">
                                        {{ $ajuan->status->label() }}
                                    </x-filament::badge>
                                </td>
                                <td class="py-3 pr-4">{{ $ajuan->processedBy?->name ?? '—' }}</td>
                                <td class="py-3 whitespace-nowrap">{{ $ajuan->created_at->format('j M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
