<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FarmSwitchController;
use App\Http\Controllers\FeatureRequestController;
use App\Http\Controllers\InstallerController;
use App\Http\Controllers\LegacyImportController;
use App\Http\Controllers\SuperuserController;
use Illuminate\Support\Facades\Route;

Route::get('/install', [InstallerController::class, 'index'])->name('installer.index');
Route::post('/install', [InstallerController::class, 'store'])->name('installer.store');
Route::get('/install/complete', [InstallerController::class, 'complete'])->name('installer.complete');

Route::middleware('installed')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login.store');
        Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
        Route::post('/register', [AuthController::class, 'register'])->name('register.store');
        Route::get('/forgot-password', [AuthController::class, 'forgotForm'])->name('password.request');
        Route::post('/forgot-password', [AuthController::class, 'forgot'])->name('password.email');
        Route::get('/reset-password/{token}', [AuthController::class, 'resetForm'])->name('password.reset');
        Route::post('/reset-password', [AuthController::class, 'reset'])->name('password.update');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/email/verify', [AuthController::class, 'verificationNotice'])->name('verification.notice');
        Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verify'])
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');
        Route::post('/email/verification-notification', [AuthController::class, 'resendVerification'])
            ->middleware('throttle:6,1')
            ->name('verification.send');

        Route::get('/no-farm', fn () => view('no-farm'))->name('no-farm');

        Route::get('/superuser', [SuperuserController::class, 'dashboard'])->name('superuser.dashboard');
        Route::get('/superuser/mcp', [SuperuserController::class, 'mcp'])->name('superuser.mcp');

        Route::middleware(['verified', 'tenant'])->group(function () {
            Route::get('/', fn () => redirect()->route('dashboard'));
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
            Route::post('/farms/switch', FarmSwitchController::class)->name('farms.switch');

            Route::get('/batches', [BatchController::class, 'index'])->name('batches.index');
            Route::post('/batches', [BatchController::class, 'store'])->name('batches.store');

            Route::get('/feature-requests', [FeatureRequestController::class, 'index'])->name('feature-requests.index');
            Route::post('/feature-requests', [FeatureRequestController::class, 'store'])->name('feature-requests.store');

            Route::get('/migration/static', [LegacyImportController::class, 'index'])->name('migration.static');
            Route::post('/migration/static', [LegacyImportController::class, 'store'])->name('migration.static.store');

            Route::view('/install-app', 'install-app')->name('install-app');
        });
    });
});
