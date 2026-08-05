<?php

namespace App\Providers;

use App\Models\Category;
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
        //
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
    }
}
