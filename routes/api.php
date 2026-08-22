<?php

use App\Http\Controllers\Api\V1\ApplicationController;
use App\Http\Controllers\Api\V1\ApplicationDocumentController;
use App\Http\Controllers\Api\V1\Auth\CitizenAuthController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ServiceCatalogController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::get('/health', HealthController::class)->name('health');

    // Public Service Catalog
    Route::get('/services', [ServiceCatalogController::class, 'index'])->name('services.index');
    Route::get('/services/categories', [ServiceCatalogController::class, 'categories'])->name('services.categories');
    Route::get('/services/{service}', [ServiceCatalogController::class, 'show'])->name('services.show');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
        Route::post('/applications', [ApplicationController::class, 'store'])->name('applications.store');
        Route::get('/applications/{application}', [ApplicationController::class, 'show'])->name('applications.show');

        Route::post('/applications/{application}/documents', [ApplicationDocumentController::class, 'store'])
            ->name('applications.documents.store');
        Route::get('/applications/{application}/documents/{document}', [ApplicationDocumentController::class, 'download'])
            ->name('applications.documents.download')
            ->scopeBindings();
        Route::delete('/applications/{application}/documents/{document}', [ApplicationDocumentController::class, 'destroy'])
            ->name('applications.documents.destroy')
            ->scopeBindings();
    });

    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::post('/register', [CitizenAuthController::class, 'register'])->name('register');
        Route::post('/login', [CitizenAuthController::class, 'login'])->name('login');

        Route::middleware(['auth:sanctum', 'citizen'])->group(function (): void {
            Route::post('/logout', [CitizenAuthController::class, 'logout'])->name('logout');
        });
    });

    Route::middleware(['auth:sanctum', 'citizen'])->group(function (): void {
        Route::get('/me', [ProfileController::class, 'show'])->name('profile.show');
        Route::patch('/me', [ProfileController::class, 'update'])->name('profile.update');

        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
            ->name('notifications.read-all');
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
            ->name('notifications.read');
    });
});
