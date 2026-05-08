<?php

use App\Http\Controllers\Pagina\PaginaCreateController;
use App\Http\Controllers\Pagina\PaginaIndexController;
use App\Http\Controllers\Pagina\PaginaShowController;
use App\Http\Controllers\Pagina\PaginaStoreController;
use App\Http\Controllers\Tenant\Configuracao\ConfiguracaoController;
use Illuminate\Support\Facades\Route;

Route::get('create', PaginaCreateController::class)->name('create');
Route::get('/', PaginaIndexController::class)->name('index');
Route::post('store', PaginaStoreController::class)->name('store');
Route::get('/{tenant}', PaginaShowController::class)->name('show');
Route::prefix('configuracao')->name('configuracao.')->group(function () {
    Route::get('/{tenant}', [ConfiguracaoController::class, 'detail'])->name('generate.detail');
    Route::post('/{tenant}', [ConfiguracaoController::class, 'forms'])->name('forms');
    Route::put('{tenantForm}/expires-at', [ConfiguracaoController::class, 'createExpiresAt'])->name('expires-at');
});
