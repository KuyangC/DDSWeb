<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FireAlarmController;

// Route utama
Route::get('/', [FireAlarmController::class, 'monitoring'])->name('home');
Route::get('/monitoring', [FireAlarmController::class, 'monitoring'])->name('monitoring');

// API routes
Route::get('/api/live-status', [FireAlarmController::class, 'getLiveStatus'])->name('fire-alarm.live-status');
Route::get('/api/check-connection', [FireAlarmController::class, 'checkConnection'])->name('fire-alarm.check-connection');
Route::post('/api/send-command', [FireAlarmController::class, 'sendCommand'])->name('fire-alarm.send-command');
Route::get('/api/command-history', [FireAlarmController::class, 'getCommandHistory'])->name('fire-alarm.command-history');