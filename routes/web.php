<?php

use App\Http\Controllers\AlertController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DoorController;
use App\Http\Controllers\DoorEventController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
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
    Route::post('/sync', [DashboardController::class, 'sync'])->name('dashboard.sync');
    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::post('/alerts/{alert}/resolve', [AlertController::class, 'resolve'])->name('alerts.resolve');
    Route::post('/alerts/{alert}/resend',  [AlertController::class, 'resend'])->name('alerts.resend');

    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
    Route::get('/logs/export/pdf',   [LogController::class, 'exportPdf'])->name('logs.export.pdf');
    Route::get('/logs/export/excel', [LogController::class, 'exportExcel'])->name('logs.export.excel');

    Route::middleware('can-door-events')->group(function () {
        Route::get('/door-events', [DoorEventController::class, 'index'])->name('door-events.index');
        Route::get('/door-events/export/pdf',   [DoorEventController::class, 'exportPdf'])->name('door-events.export.pdf');
        Route::get('/door-events/export/excel', [DoorEventController::class, 'exportExcel'])->name('door-events.export.excel');
    });

    Route::middleware('can-doors')->group(function () {
        Route::get('/doors', [DoorController::class, 'index'])->name('doors.index');
        Route::post('/doors/{id}/open', [DoorController::class, 'open'])->name('doors.open');
    });

    // Admin-only routes
    Route::middleware('admin')->group(function () {
        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::put('/settings/email', [SettingsController::class, 'updateEmail'])->name('settings.email.update');
        Route::post('/settings/email/test', [SettingsController::class, 'testEmail'])->name('settings.email.test');
        Route::put('/settings/whatsapp', [SettingsController::class, 'updateWhatsApp'])->name('settings.whatsapp.update');
        Route::post('/settings/whatsapp/test', [SettingsController::class, 'testWhatsApp'])->name('settings.whatsapp.test');
        Route::put('/settings/connection', [SettingsController::class, 'updateConnection'])->name('settings.connection.update');
        Route::post('/settings/connection/test', [SettingsController::class, 'testConnection'])->name('settings.connection.test');
        Route::post('/settings/test', [SettingsController::class, 'test'])->name('settings.test');

        Route::resource('users', UserController::class)->except(['show']);
    });
});
