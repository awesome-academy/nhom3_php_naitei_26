<?php

use App\Http\Controllers\Admin\ActivityLogs\ActivityLogController;
use App\Http\Controllers\Admin\Applications\ApplicationController;
use App\Http\Controllers\Admin\Auth\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DataExportController;
use App\Http\Controllers\Admin\Departments\DepartmentCandidateController;
use App\Http\Controllers\Admin\Departments\DepartmentController;
use App\Http\Controllers\Admin\Departments\DepartmentLeaderController;
use App\Http\Controllers\Admin\Departments\DepartmentMemberController;
use App\Http\Controllers\Admin\Departments\TransferDepartmentMemberController;
use App\Http\Controllers\Admin\ServiceCategories\ServiceCategoryController;
use App\Http\Controllers\Admin\ServiceTypes\ServiceTypeController;
use App\Http\Controllers\Admin\UserImportController;
use App\Http\Controllers\Admin\Users\UserController;
use App\Http\Controllers\Api\V1\Auth\GoogleCitizenAuthController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'citizen.app')->name('citizen.app');

Route::view('/ui-showcase', 'ui-showcase')->name('ui-showcase');

Route::prefix('api/v1/auth/google')
    ->name('api.v1.auth.google.')
    ->group(function (): void {
        Route::get('/redirect', [GoogleCitizenAuthController::class, 'redirect'])->name('redirect');
        Route::get('/callback', [GoogleCitizenAuthController::class, 'callback'])->name('callback');
        Route::get('/pending', [GoogleCitizenAuthController::class, 'pending'])->name('pending');
        Route::post('/complete', [GoogleCitizenAuthController::class, 'complete'])->name('complete');
    });

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [AdminAuthController::class, 'create'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'store'])->name('login.store');
    });

    Route::middleware(['auth', 'internal'])->group(function (): void {
        Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/departments/manager-candidates', [DepartmentCandidateController::class, 'managerCandidates'])
            ->name('departments.manager-candidates');
        Route::patch('/departments/{department}/leader', [DepartmentLeaderController::class, 'update'])
            ->name('departments.leader.update');
        Route::get('/departments/{department}/member-candidates', [DepartmentCandidateController::class, 'memberCandidates'])
            ->name('departments.member-candidates');
        Route::post('/departments/{department}/members', [DepartmentMemberController::class, 'store'])
            ->name('departments.members.store');
        Route::get('/departments/{department}/members/{member}/transfer-targets', [DepartmentCandidateController::class, 'transferTargets'])
            ->scopeBindings()
            ->name('departments.members.transfer-targets');
        Route::post('/departments/{department}/members/{member}/transfer', TransferDepartmentMemberController::class)
            ->scopeBindings()
            ->name('departments.members.transfer');
        Route::delete('/departments/{department}/members/{member}', [DepartmentMemberController::class, 'destroy'])
            ->scopeBindings()
            ->name('departments.members.destroy');
        Route::resource('departments', DepartmentController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
        Route::resource('service-categories', ServiceCategoryController::class);
        Route::resource('service-types', ServiceTypeController::class);

        // User Import & Data Export routes
        Route::get('/export/{resource}', [DataExportController::class, 'export'])->name('export');
        Route::get('/users/import', [UserImportController::class, 'index'])->name('users.import');
        Route::post('/users/import/citizens', [UserImportController::class, 'importCitizens'])->name('users.import.citizens');
        Route::post('/users/import/staff', [UserImportController::class, 'importStaff'])->name('users.import.staff');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [UserController::class, 'show'])->whereNumber('user')->name('users.show');
        Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])
            ->whereNumber('user')
            ->name('users.status.update');
        Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
        Route::get('/applications/{application}', [ApplicationController::class, 'show'])->name('applications.show');
        Route::post('/applications/{application}/assign', [ApplicationController::class, 'assign'])->name('applications.assign');
        Route::post('/applications/{application}/claim', [ApplicationController::class, 'claim'])->name('applications.claim');
        Route::post('/applications/{application}/start-processing', [ApplicationController::class, 'startProcessing'])->name('applications.start-processing');
        Route::post('/applications/{application}/request-supplement', [ApplicationController::class, 'requestSupplement'])->name('applications.request-supplement');
        Route::post('/applications/{application}/resume', [ApplicationController::class, 'resume'])->name('applications.resume');
        Route::post('/applications/{application}/approve', [ApplicationController::class, 'approve'])->name('applications.approve');
        Route::post('/applications/{application}/reject', [ApplicationController::class, 'reject'])->name('applications.reject');
        Route::post('/applications/{application}/submit-for-approval', [ApplicationController::class, 'submitForApproval'])->name('applications.submit-for-approval');
        Route::post('/applications/{application}/return', [ApplicationController::class, 'returnToProcessing'])->name('applications.return');
        Route::post('/applications/{application}/result-documents', [ApplicationController::class, 'storeResultDocument'])->name('applications.result-documents.store');
        Route::get('/applications/{application}/documents/{document}/download', [ApplicationController::class, 'downloadDocument'])
            ->scopeBindings()
            ->name('applications.documents.download');
        Route::get('/activity-logs', ActivityLogController::class)->name('activity-logs.index');
    });
});

Route::view('/{path}', 'citizen.app')
    ->where('path', '^(?!admin(?:/|$)|api(?:/|$)|docs(?:/|$)).*$')
    ->name('citizen.fallback');
