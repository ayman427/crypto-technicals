<?php

use App\Http\Controllers\BinanceController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    Route::get('/search', [BinanceController::class, 'search'])->name('search');
    Route::get('/pair/{symbol}', [BinanceController::class, 'pair'])->name('pair.details');

// optional json api endpoints if you want them
    Route::get('/api/ticker/{symbol}', [BinanceController::class, 'ticker']);
    Route::get('/api/klines/{symbol}/{interval?}', [BinanceController::class, 'klines']);
});

require __DIR__ . '/auth.php';
