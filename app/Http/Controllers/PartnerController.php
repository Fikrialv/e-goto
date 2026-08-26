<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVendorApplicationRequest;
use App\Models\VendorApplication;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Onboarding mitra (D8). Halaman ini publik: calon mitra menilai dulu sebelum
 * memutuskan, dan memaksa login sebelum membaca kriteria justru mengusir orang
 * yang belum tahu ini cocok untuknya atau tidak.
 */
class PartnerController extends Controller
{
    public function show(): View
    {
        return view('pages.jadi-mitra');
    }

    public function store(StoreVendorApplicationRequest $request): RedirectResponse
    {
        $berkas = [];

        foreach ($request->file('documents', []) as $dokumen) {
            $berkas[] = [
                'nama' => $dokumen->getClientOriginalName(),
                'path' => $dokumen->store(config('partner.document_directory'), config('partner.document_disk')),
            ];
        }

        VendorApplication::create([
            ...$request->safe()->except('documents'),
            'documents' => $berkas,
        ]);

        return redirect()
            ->route('partners.show')
            ->with('status', 'Pengajuan terkirim. Tim kami menghubungi Anda lewat WhatsApp untuk jadwal ngobrol.');
    }

    /**
     * Salurkan dokumen pengajuan ke layar admin. Berkasnya di disk non-publik
     * dan memuat identitas penanggung jawab, jadi hanya boleh keluar lewat
     * route ini — penjaganya `role:admin` di berkas route.
     */
    public function document(VendorApplication $application, int $index): StreamedResponse
    {
        $dokumen = $application->documents[$index] ?? null;

        abort_if($dokumen === null, 404);

        $disk = Storage::disk(config('partner.document_disk'));

        abort_unless($disk->exists($dokumen['path']), 404);

        return $disk->response($dokumen['path'], $dokumen['nama'] ?? null);
    }
}
