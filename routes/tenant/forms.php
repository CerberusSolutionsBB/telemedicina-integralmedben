<?php

use App\Http\Controllers\PublicFormController;
use App\Http\Controllers\Tenant\Form\FormIndexController;
use App\Http\Controllers\Tenant\Form\FormShowController;
use Illuminate\Support\Facades\Route;

Route::get('/public-form', [PublicFormController::class, 'showLoginForm'])
    ->name('public_form.store');

Route::middleware('auth')->group(function () {
    Route::prefix('meus-formularios')
        ->name('meus-formularios.')
        ->group(function () {
            Route::get('/', FormIndexController::class)->name('index');
            Route::get('/{form}', FormShowController::class)->name('show');
        });
});
