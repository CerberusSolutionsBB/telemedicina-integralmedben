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
        Route::put('/cartao-dinamico/cores', 'updateCartaoDinamicoCores')->name('cartao-dinamico.cores');
        Route::post('/cartao-dinamico/{tipo}', 'storeCartaoDinamicoImagem')
            ->whereIn('tipo', ['logo', 'frente', 'verso'])
            ->name('cartao-dinamico.imagem.store');
        Route::delete('/cartao-dinamico/{tipo}', 'destroyCartaoDinamicoImagem')
            ->whereIn('tipo', ['logo', 'frente', 'verso'])
            ->name('cartao-dinamico.imagem.destroy');
        Route::put('/cartao-dinamico/qrcode', 'updateCartaoQrCode')->name('cartao-dinamico.qrcode');
        Route::put('/cartao-dinamico/toggle', 'toggleCartaoDinamico')->name('cartao-dinamico.toggle');
    });
