<?php

use App\Http\Controllers\PrivateAuthController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SiteSyncController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [PrivateAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [PrivateAuthController::class, 'login'])->name('login.attempt');
Route::post('/logout', [PrivateAuthController::class, 'logout'])->name('logout');

Route::middleware('backoffice.auth')->group(function () {
    Route::get('/', [SiteController::class, 'index'])->name('dashboard');
    Route::resource('sites', SiteController::class)->except(['index', 'destroy']);
    Route::post('sites/{site}/toggle', [SiteController::class, 'toggle'])->name('sites.toggle');
    Route::post('sites/{site}/sync', [SiteSyncController::class, 'sync'])->name('sites.sync');
    Route::post('sync-all', [SiteSyncController::class, 'syncAll'])->name('sync.all');
});
