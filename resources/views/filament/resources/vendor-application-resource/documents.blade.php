@php($dokumen = $getState() ?? [])

@if (empty($dokumen))
    <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada dokumen dilampirkan.</p>
@else
    <ul class="space-y-1.5">
        @foreach ($dokumen as $index => $berkas)
            <li>
                {{-- Berkas ada di disk non-publik; route ini yang menyalurkannya
                     dengan penjaga role:admin. --}}
                <a href="{{ route('admin.partners.document', [$getRecord(), $index]) }}"
                   target="_blank" rel="noopener"
                   class="text-sm text-primary-600 underline underline-offset-4 dark:text-primary-400">
                    {{ $berkas['nama'] ?? 'Dokumen '.($index + 1) }}
                </a>
            </li>
        @endforeach
    </ul>
@endif
