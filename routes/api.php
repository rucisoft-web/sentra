<?php

use App\Http\Controllers\Api\DocumentApiController;
use Illuminate\Support\Facades\Route;

// API integrasi (Asset Care, Ruci BSC, CRM GPCH, VHP) — service account dengan Bearer token, read-only metadata.
Route::prefix('v1')->middleware(['api.token', 'throttle:60,1'])->group(function () {
    Route::get('/documents', [DocumentApiController::class, 'index']);
    Route::get('/documents/{id}', [DocumentApiController::class, 'show']);
    Route::get('/links/{system}/{record}', [DocumentApiController::class, 'links']);
    Route::post('/links', [DocumentApiController::class, 'storeLink']);
});
