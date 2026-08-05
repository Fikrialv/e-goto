<?php

namespace App\Providers;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
    }
}
