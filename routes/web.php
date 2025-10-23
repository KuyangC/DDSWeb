<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FireAlarmController; // ✅ Perbaikan namespace

Route::prefix('fire-alarm')->group(function () { // ✅ Perbaikan syntax
    Route::get('/dashboard', [FireAlarmController::class, 'dashboard'])->name('fire-alarm.dashboard');
    Route::get('/system-overview', [FireAlarmController::class, 'systemOverview'])->name('fire-alarm.overview'); // ✅ Perbaikan method name
    Route::get('/zone-status/live', [FireAlarmController::class, 'getLiveZoneStatus'])->name('fire-alarm.zone-status.live'); // ✅ Tambah kurung tutup
    Route::get('/zone/{zoneId}', [FireAlarmController::class, 'zoneDetails'])->name('fire-alarm.zone-details'); // ✅ Perbaikan parameter
    Route::get('/alarm-history', [FireAlarmController::class, 'alarmHistory'])->name('fire-alarm.alarm-history');
});

// Atau untuk home page langsung ke fire alarm monitoring
Route::get('/', [FireAlarmController::class, 'monitoring'])->name('home'); // ✅ Perbaikan syntax
Route::get('/monitoring', [FireAlarmController::class, 'monitoring'])->name('monitoring'); // ✅ Tambah route monitoring