<?php

use App\Http\Controllers\Auth\AuthProfileController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

InitializeTenancyByDomain::$onFail = function ($e, $request, $next) {
    return $next($request);
};

Route::middleware([InitializeTenancyByDomain::class])->group(function () {
    Route::get('/', [AuthProfileController::class, 'edit'])
        ->name('edit');

    Route::patch('/', [AuthProfileController::class, 'update'])
        ->name('update');

    Route::put('/senha', [AuthProfileController::class, 'updatePassword'])
        ->name('password.update');

    Route::delete('/', [AuthProfileController::class, 'destroy'])
        ->name('destroy');
});
