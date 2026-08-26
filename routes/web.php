<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleLoginController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TripController;
use Illuminate\Support\Facades\Route;

/*
 * Tiga route di bawah SENGAJA tanpa middleware `auth` (PLAN.md §5.5).
 * Browsing publik harus nol hambatan; login baru jadi gerbang tepat sebelum
 * booking (D3). Jangan tambahkan middleware auth ke route ini.
 */
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/kategori/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');
Route::get('/trip/{trip:slug}', [TripController::class, 'show'])->name('trips.show');

/*
 * Halaman legal & bantuan (D7.5). Ikut aturan yang sama: publik, tanpa `auth` —
 * syarat refund dan kebijakan privasi justru paling sering dibaca orang yang
 * belum punya akun, tepat saat memutuskan mau pesan atau tidak.
 */
Route::get('/faq', [PageController::class, 'faq'])->name('pages.faq');
Route::get('/syarat-ketentuan', [PageController::class, 'terms'])->name('pages.terms');
Route::get('/kebijakan-privasi', [PageController::class, 'privacy'])->name('pages.privacy');

/*
 * Auth customer. Nama route `login` wajib persis begitu: middleware `auth`
 * bawaan Laravel memakainya saat mengusir tamu, dan `redirect()->guest()`
 * itulah yang menyimpan `url.intended` — gerbang booking tidak perlu kode
 * penyimpan tujuan sendiri.
 */
Route::middleware('guest')->group(function () {
    Route::get('/daftar', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/daftar', [RegisteredUserController::class, 'store'])->name('register.store');

    Route::get('/masuk', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/masuk', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:masuk')
        ->name('login.store');

    Route::get('/auth/google/redirect', [GoogleLoginController::class, 'redirect'])->name('social.redirect');
    Route::get('/auth/google/callback', [GoogleLoginController::class, 'callback'])->name('social.callback');
});

Route::post('/keluar', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
 * Area customer. `role:customer` menolak admin/vendor dengan 403 (bukan
 * redirect) — halaman ini memang bukan milik mereka.
 */
Route::middleware(['auth', 'role:customer'])->group(function () {
    Route::get('/profil/lengkapi', [ProfileController::class, 'complete'])->name('profile.complete');
    Route::get('/profil/lewati', [ProfileController::class, 'skip'])->name('profile.skip');
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/booking-saya', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/booking/{schedule}', [BookingController::class, 'create'])->name('bookings.create');
    Route::post('/booking/{schedule}', [BookingController::class, 'store'])
        ->middleware('throttle:booking')
        ->name('bookings.store');

    /*
     * Halaman pembayaran dipanggil dengan kode booking, bukan id berurutan —
     * id yang bisa ditebak mengundang orang mencoba membuka booking tetangga.
     * Kepemilikan tetap diperiksa di controller, ini cuma lapis pertama.
     */
    Route::get('/booking/{booking:code}/bayar', [PaymentController::class, 'show'])->name('payments.show');
    Route::post('/booking/{booking:code}/bayar/konfirmasi', [PaymentController::class, 'confirmMethod'])
        ->name('payments.confirm');
    Route::post('/booking/{booking:code}/bayar', [PaymentController::class, 'store'])
        ->middleware('throttle:upload-bukti')
        ->name('payments.store');
    Route::get('/booking/{booking:code}/bukti', [PaymentController::class, 'proof'])->name('payments.proof');
    Route::get('/booking/{booking:code}/tiket', [TicketController::class, 'show'])->name('tickets.show');
});

/*
 * Bukti bayar untuk layar verifikasi admin. Berkasnya ada di disk non-publik,
 * jadi Filament tidak bisa menautkannya langsung — route inilah yang
 * menyalurkannya, dengan `role:admin` sebagai penjaganya.
 */
Route::get('/bukti-bayar/{payment}', [PaymentController::class, 'adminProof'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.payments.proof');
