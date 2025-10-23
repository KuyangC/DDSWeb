<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FireAlarmController;

// Route utama untuk monitoring 63 slave
Route::get('/', [FireAlarmController::class, 'monitoring'])->name('home');
Route::get('/monitoring', [FireAlarmController::class, 'monitoring'])->name('monitoring');

// API untuk live data (harus match dengan yang di blade)
Route::get('/api/live-status', [FireAlarmController::class, 'getLiveStatus'])->name('fire-alarm.live-status');

// Route untuk testing/generate data dummy
Route::get('/test/generate-data', [FireAlarmController::class, 'generateTestData'])->name('fire-alarm.test-data');

// Optional: Route group jika mau organized
Route::prefix('fire-alarm')->name('fire-alarm.')->group(function () {
    Route::get('/dashboard', [FireAlarmController::class, 'monitoring'])->name('dashboard');
    Route::get('/system-overview', [FireAlarmController::class, 'monitoring'])->name('overview');
});