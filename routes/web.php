<?php

use App\Http\Controllers\AlertController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

// Public webhook — must be reachable by Meta's servers, no auth required.
Route::get('/api/whatsapp/webhook',  [WhatsAppWebhookController::class, 'verify']);
Route::post('/api/whatsapp/webhook', [WhatsAppWebhookController::class, 'handle']);

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::post('/alerts/{alert}/resolve', [AlertController::class, 'resolve'])->name('alerts.resolve');
    Route::post('/alerts/{alert}/resend',  [AlertController::class, 'resend'])->name('alerts.resend');
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::put('/settings/email', [SettingsController::class, 'updateEmail'])->name('settings.email.update');
    Route::post('/settings/email/test', [SettingsController::class, 'testEmail'])->name('settings.email.test');
    Route::put('/settings/whatsapp', [SettingsController::class, 'updateWhatsApp'])->name('settings.whatsapp.update');
    Route::post('/settings/whatsapp/test', [SettingsController::class, 'testWhatsApp'])->name('settings.whatsapp.test');
    Route::put('/settings/connection', [SettingsController::class, 'updateConnection'])->name('settings.connection.update');
    Route::post('/settings/connection/test', [SettingsController::class, 'testConnection'])->name('settings.connection.test');
    Route::post('/settings/test', [SettingsController::class, 'test'])->name('settings.test');
});
