<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Keamanan;
use App\Http\Middleware\RequireTwoFactor;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class VendorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('vendor')
            ->path('vendor')
            ->login()
            // Teal logo E-GOTO — sama dengan panel admin, lihat AdminPanelProvider.
            ->brandName('E-GOTO')
            ->brandLogo(asset('images/Logo1.svg'))
            ->brandLogoHeight('1.6rem')
            ->favicon(asset('images/logo2.svg'))
            ->colors([
                'primary' => Color::hex('#077C82'),
            ])
            ->discoverResources(in: app_path('Filament/Vendor/Resources'), for: 'App\\Filament\\Vendor\\Resources')
            ->discoverPages(in: app_path('Filament/Vendor/Pages'), for: 'App\\Filament\\Vendor\\Pages')
            ->pages([
                Pages\Dashboard::class,
                // Kelas yang sama dengan panel admin, didaftarkan eksplisit
                // karena discoverPages panel ini hanya menyapu folder Vendor.
                // Mitra melihat data peserta dan menjalankan check-in, jadi
                // akunnya juga pantas punya langkah kedua.
                Keamanan::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Vendor/Widgets'), for: 'App\\Filament\\Vendor\\Widgets')
            /*
             * Widget bawaan starter kit dicabut: FilamentInfoWidget memajang
             * versi Filament + tautan dokumentasi/GitHub ke staf yang tidak
             * punya urusan dengan itu, dan AccountWidget mengulang identitas
             * yang sudah ada di pojok kanan atas.
             */
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                // Berjalan setelah Authenticate, jadi user pasti sudah dikenali.
                // Akun yang belum menyalakan 2FA lewat begitu saja — memaksanya
                // ke seluruh akun staf sekaligus akan mengunci pemilik project
                // keluar begitu migration jalan.
                RequireTwoFactor::class,
            ]);
    }
}
