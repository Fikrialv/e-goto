<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HomeController;
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
