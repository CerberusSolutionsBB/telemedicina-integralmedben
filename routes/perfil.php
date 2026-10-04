<?php

use App\Http\Controllers\Auth\AuthProfileController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

Route::middleware([InitializeTenancyByDomain::class])->group(function () {
    Route::get('/', [AuthProfileController::class, 'edit'])
        ->name('edit');

    Route::patch('/', [AuthProfileController::class, 'update'])
        ->name('update');

    Route::put('/senha', [AuthProfileController::class, 'updatePassword'])
        ->name('password.update');

    Route::delete('/', [AuthProfileController::class, 'destroy'])
        ->name('destroy');

    Route::get('/foto', [AuthProfileController::class, 'showFoto'])
        ->name('foto.show');

    // POST: upload de arquivo pelo Inertia não funciona com PUT/PATCH.
    Route::post('/foto', [AuthProfileController::class, 'updateFoto'])
        ->name('foto.update');

    Route::delete('/foto', [AuthProfileController::class, 'destroyFoto'])
        ->name('foto.destroy');
});
