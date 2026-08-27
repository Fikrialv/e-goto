<?php

namespace App\Http\Controllers;

use App\Contracts\MessagingService;
use App\Http\Requests\PrivateTripRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Request private trip (D12).
 *
 * Rombongan di atas batas peserta per booking diarahkan ke sini (PLAN.md §5.6).
 * Form ini tidak membuat booking dan tidak menyimpan data — hasilnya tautan
 * `wa.me` berisi pesan siap kirim, dibangun lewat MessagingService supaya
 * nomor tujuan dan bentuk pesan tetap satu pintu.
 */
class PrivateTripController extends Controller
{
    public function show(): View
    {
        return view('pages.private-trip');
    }

    public function store(PrivateTripRequest $request, MessagingService $messaging): RedirectResponse
    {
        $tautan = $messaging->requestPrivateTripForm($request->validated());

        return redirect()
            ->route('private-trip.show')
            ->with('tautanWhatsApp', $tautan)
            ->with('status', 'Pesan Anda sudah disiapkan. Tekan tombol di bawah untuk mengirimnya lewat WhatsApp.');
    }
}
