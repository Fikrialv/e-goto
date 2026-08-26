<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Halaman statis wajib sebelum rilis (PLAN.md D7.5): FAQ, Syarat & Ketentuan,
 * Kebijakan Privasi. Isinya di Blade, bukan database — tiga halaman ini jarang
 * berubah dan perubahannya perlu terlihat di riwayat git, bukan disunting diam
 * lewat panel admin.
 */
class PageController extends Controller
{
    public function faq(): View
    {
        return view('pages.faq');
    }

    public function terms(): View
    {
        return view('pages.syarat-ketentuan');
    }

    public function privacy(): View
    {
        return view('pages.kebijakan-privasi');
    }
}
