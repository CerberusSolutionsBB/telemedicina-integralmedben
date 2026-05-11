<?php

use App\Http\Controllers\Tenant\Configuracao\ConfiguracaoController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->prefix('configuracao')
    ->name('configuracao.')
    ->controller(ConfiguracaoController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/logo', 'updateLogo')->name('logo.update');
    });
