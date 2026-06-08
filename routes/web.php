<?php

use App\Http\Controllers\PrivateAuthController;
use App\Http\Controllers\BackofficeSettingsController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SiteSyncController;
use App\Http\Controllers\SnapshotController;
use App\Http\Controllers\SyncErrorController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [PrivateAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [PrivateAuthController::class, 'login'])->name('login.attempt');
Route::post('/logout', [PrivateAuthController::class, 'logout'])->name('logout');

Route::middleware('backoffice.auth')->group(function () {
    Route::get('/', [SiteController::class, 'index'])->name('dashboard');
    Route::get('settings', [BackofficeSettingsController::class, 'edit'])->name('backoffice-settings.edit');
    Route::post('settings', [BackofficeSettingsController::class, 'update'])->name('backoffice-settings.update');
    Route::post('sites/reorder', [SiteController::class, 'reorder'])->name('sites.reorder');
    Route::resource('sites', SiteController::class)->except(['index', 'destroy']);
    Route::post('sites/{site}/toggle', [SiteController::class, 'toggle'])->name('sites.toggle');
    Route::post('sites/{site}/sync', [SiteSyncController::class, 'sync'])->name('sites.sync');
    Route::post('sync-all', [SiteSyncController::class, 'syncAll'])->name('sync.all');
    Route::get('sync-errors', [SyncErrorController::class, 'index'])->name('sync-errors.index');
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('sites/{site}/snapshots', [SnapshotController::class, 'index'])->name('sites.snapshots');
    Route::get('marketing', [MarketingController::class, 'index'])->name('marketing.index');
});
