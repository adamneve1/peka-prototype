<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Kiosk\Rate as KioskRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use App\Livewire\Peka;

RateLimiter::for('ratings', fn(Request $r) => [Limit::perMinute(10)->by($r->ip())]);

Route::middleware('throttle:ratings')->get('/kiosk', KioskRate::class)->name('kiosk');

// PAGE BARU
Route::get('/baru', Peka::class)->name('peka.page');

Route::view('/', 'landing')->name('landing');
// routes/web.php
//Route::view('/', 'peka-landing')->name('landing');   // landing + form di 1 file
