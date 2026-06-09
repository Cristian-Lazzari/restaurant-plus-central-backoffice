<?php

use App\Http\Controllers\PrivateAuthController;
use App\Http\Controllers\BackofficeSettingsController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SiteSyncController;
use App\Http\Controllers\SnapshotController;
use App\Http\Controllers\SyncErrorController;
use App\Http\Controllers\TodolistController;
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
    Route::get('sync-errors', fn() => redirect()->route('backoffice-settings.edit', ['tab' => 'errori']))->name('sync-errors.index');
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('sites/{site}/snapshots', [SnapshotController::class, 'index'])->name('sites.snapshots');
    // ── Todolist (Piano 90 Giorni) ──────────────────────────────────────────
    Route::get('todolist', [TodolistController::class, 'index'])->name('todolist.index');
    Route::post('todolist/toggle', [TodolistController::class, 'toggle'])->name('todolist.toggle');
    Route::post('todolist/reset', [TodolistController::class, 'reset'])->name('todolist.reset');
    Route::post('todolist/hole', [TodolistController::class, 'storeHole'])->name('todolist.hole.store');
    Route::delete('todolist/hole/{id}', [TodolistController::class, 'destroyHole'])->name('todolist.hole.destroy');
    Route::post('todolist/reseed', [TodolistController::class, 'reseedTasks'])->name('todolist.reseed');
    Route::post('todolist/lock/{id}', [TodolistController::class, 'toggleLock'])->name('todolist.lock');

    // ── Pipeline CRM ────────────────────────────────────────────────────────
    Route::get('pipeline', [PipelineController::class, 'index'])->name('pipeline.index');
    Route::get('pipeline/leads', [PipelineController::class, 'leads'])->name('pipeline.leads');
    Route::post('pipeline/leads', [PipelineController::class, 'storeLead'])->name('pipeline.leads.store');
    Route::put('pipeline/leads/{site}', [PipelineController::class, 'updateLead'])->name('pipeline.leads.update');
    Route::delete('pipeline/leads/{site}', [PipelineController::class, 'destroyLead'])->name('pipeline.leads.destroy');
    Route::get('pipeline/smm', [PipelineController::class, 'smmList'])->name('pipeline.smm');
    Route::post('pipeline/smm', [PipelineController::class, 'storeSmm'])->name('pipeline.smm.store');
    Route::put('pipeline/smm/{smm}', [PipelineController::class, 'updateSmm'])->name('pipeline.smm.update');
    Route::delete('pipeline/smm/{smm}', [PipelineController::class, 'destroySmm'])->name('pipeline.smm.destroy');
    Route::get('pipeline/stats', [PipelineController::class, 'stats'])->name('pipeline.stats');
    Route::post('pipeline/seed', [PipelineController::class, 'seed'])->name('pipeline.seed');
    Route::get('pipeline/export', [PipelineController::class, 'exportCsv'])->name('pipeline.export');
});
