<?php

use App\Http\Controllers\DeploymentStatusController;
use App\Http\Controllers\ForgeCredentialController;
use App\Http\Controllers\LanguagePreferenceController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/language', [LanguagePreferenceController::class, 'create'])
    ->name('language-preference.create');
Route::post('/language', [LanguagePreferenceController::class, 'store'])
    ->name('language-preference.store');

Route::middleware('language-preference')->group(function (): void {
    Route::get('/', [SiteController::class, 'index'])
        ->name('sites.index');
    Route::get('/connections', [ForgeCredentialController::class, 'index'])
        ->name('forge-credentials.index');
    Route::post('/forge-credentials', [ForgeCredentialController::class, 'store'])
        ->name('forge-credentials.store');
    Route::post('/preferences/language', [LanguagePreferenceController::class, 'update'])
        ->name('language-preference.update');
    Route::post('/deployment-status/refresh', DeploymentStatusController::class)
        ->name('deployment-status.refresh');
});
