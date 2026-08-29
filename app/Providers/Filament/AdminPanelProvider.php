<?php

namespace App\Providers\Filament;

use App\Filament\InisialAvatarProvider;
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

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            // Teal logo E-GOTO (#077C82), bukan palet bawaan Filament — panel staf
            // dan sisi customer harus terbaca sebagai satu produk yang sama.
            ->brandName('E-GOTO')
            ->brandLogo(asset('images/Logo1.svg'))
            ->brandLogoHeight('1.6rem')
            ->favicon(asset('images/logo2.svg'))
            // Avatar dirender lokal sebagai SVG data URI. Bawaan Filament
            // menembak ui-avatars.com tiap muat halaman — satu request luar
            // untuk dua huruf, dan diblokir begitu CSP ditegakkan.
            ->defaultAvatarProvider(InisialAvatarProvider::class)
            ->colors([
                'primary' => Color::hex('#077C82'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
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
