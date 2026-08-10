<?php

namespace App\Providers;

use App\Contracts\MessagingService;
use App\Contracts\PaymentGateway;
use App\Contracts\TicketSigner;
use App\Models\Category;
use App\Services\Messaging\WhatsAppLinkService;
use App\Services\Payment\ManualQrisGateway;
use App\Services\Ticket\HmacTicketSigner;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /*
         * Tiga batas yang sengaja dipasang sejak V1 (PLAN.md §3): pembayaran,
         * notifikasi, dan penandatangan tiket dipanggil lewat interface. Saat
         * nanti pindah ke Midtrans / WhatsApp Business API, yang diganti cuma
         * baris di bawah ini — alur booking, verifikasi, dan tiket tidak ikut
         * dibongkar.
         */
        $this->app->bind(PaymentGateway::class, ManualQrisGateway::class);
        $this->app->bind(MessagingService::class, WhatsAppLinkService::class);
        $this->app->bind(TicketSigner::class, HmacTicketSigner::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
         * Menu kategori muncul di header & footer setiap halaman publik.
         * Diisi lewat composer supaya tiap controller tidak perlu mengoper
         * variabel yang sama, dan di-cache karena isinya nyaris statis.
         */
        View::composer('components.layouts.app', function ($view) {
            $view->with('navCategories', Cache::remember(
                'nav.categories',
                now()->addHour(),
                fn () => Category::query()->active()->orderBy('sort_order')->get()
            ));
        });

        /*
         * Rem percobaan login. Dikunci per kombinasi email+IP, bukan IP saja:
         * banyak calon customer berbagi satu IP publik (kantor, kampus,
         * seluler ber-NAT), dan pembatas per-IP membuat satu penyerang bisa
         * mengunci semua orang di jaringan itu.
         */
        RateLimiter::for('masuk', fn (Request $request) => Limit::perMinute(5)
            ->by(Str::lower((string) $request->input('email')).'|'.$request->ip()));

        /*
         * Dua titik tulis milik customer. Dibatasi per akun (bukan per IP):
         * keduanya hanya bisa diakses setelah login, dan pembatas per akun
         * tidak ikut menghukum orang lain yang kebetulan satu IP publik.
         *
         * Booking menahan kuota sejak dibuat, jadi tanpa rem ini satu akun
         * bisa memborong semua kursi dengan booking yang tidak pernah dibayar.
         */
        RateLimiter::for('booking', fn (Request $request) => Limit::perMinute(5)
            ->by((string) $request->user()?->id));

        RateLimiter::for('upload-bukti', fn (Request $request) => Limit::perMinute(10)
            ->by((string) $request->user()?->id));
    }
}
