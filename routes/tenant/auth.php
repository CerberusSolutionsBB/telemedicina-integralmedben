<?php

use App\Http\Controllers\Tenant\TenantAuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {

    if (Auth::check()) {
        return redirect()->route('cpanel.patients.index');
    }

    return redirect()->route('login');
});

Route::get('/login', [TenantAuthController::class, 'showLoginForm'])
    ->name('login');

Route::post('/login', [TenantAuthController::class, 'login'])
    ->name('tenant.login.store');

Route::get('/admin/login', [TenantAuthController::class, 'showLoginForm'])
    ->name('tenant.admin.login');

Route::post('/admin/login', [TenantAuthController::class, 'login'])
    ->name('tenant.admin.login.store');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [TenantAuthController::class, 'logout'])
        ->name('tenant.logout');

    Route::post('/admin/logout', [TenantAuthController::class, 'logout'])
        ->name('tenant.admin.logout');
});
