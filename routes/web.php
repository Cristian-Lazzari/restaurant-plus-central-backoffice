<?php

use App\Http\Controllers\PrivateAuthController;
use App\Http\Controllers\BackofficeSettingsController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SiteSyncController;
use App\Http\Controllers\SnapshotController;
use App\Http\Controllers\SyncErrorController;
use App\Http\Controllers\MarketingPlanController;
use App\Http\Controllers\TodolistController;
use App\Http\Controllers\UserController;
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
    Route::delete('todolist/task/{id}', [TodolistController::class, 'deleteTask'])->name('todolist.task.delete');

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

    // ── Pipeline Marketing (strategia social per ristorante) ────────────────
    Route::get('marketing', [MarketingPlanController::class, 'index'])->name('marketing.index');
    Route::get('sites/{site}/marketing', [MarketingPlanController::class, 'show'])->name('marketing.show');
    Route::post('sites/{site}/marketing/import', [MarketingPlanController::class, 'import'])->name('marketing.import');
    Route::delete('sites/{site}/marketing', [MarketingPlanController::class, 'destroy'])->name('marketing.destroy');
    Route::post('sites/{site}/marketing/meta', [MarketingPlanController::class, 'updateMeta'])->name('marketing.meta');
    Route::post('sites/{site}/marketing/items', [MarketingPlanController::class, 'storeItem'])->name('marketing.items.store');
    Route::post('marketing/items/{item}/toggle', [MarketingPlanController::class, 'toggleItem'])->name('marketing.items.toggle');
    Route::post('marketing/items/{item}/move', [MarketingPlanController::class, 'moveItem'])->name('marketing.items.move');
    Route::post('marketing/items/{item}/full', [MarketingPlanController::class, 'updateItemFull'])->name('marketing.items.updateFull');
    Route::delete('marketing/items/{item}', [MarketingPlanController::class, 'destroyItem'])->name('marketing.items.destroy');
    Route::post('marketing/items/{item}', [MarketingPlanController::class, 'updateItem'])->name('marketing.items.update');

    // ── Utenti (solo CEO — gli account ristorante vengono reindirizzati dal middleware) ──
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});
