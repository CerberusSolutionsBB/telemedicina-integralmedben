<?php

use App\Http\Controllers\Pagina\PaginaCreateController;
use App\Http\Controllers\Pagina\PaginaDestroyController;
use App\Http\Controllers\Pagina\PaginaIndexController;
use App\Http\Controllers\Pagina\PaginaShowController;
use App\Http\Controllers\Pagina\PaginaStoreController;
use App\Http\Controllers\Pagina\PaginaUserController;
use App\Http\Controllers\Tenant\Configuracao\ConfiguracaoController;
use Illuminate\Support\Facades\Route;

Route::get('create', PaginaCreateController::class)->name('create');
Route::get('/', PaginaIndexController::class)->name('index');
Route::post('store', PaginaStoreController::class)->name('store');
Route::get('/{tenant}', PaginaShowController::class)->name('show');
Route::delete('/{tenant}', PaginaDestroyController::class)->name('destroy');
Route::prefix('users')->name('users.')->group(function () {
    Route::get('/{tenant}', [PaginaUserController::class, 'index'])->name('index');
    Route::get('/{tenant}/create', [PaginaUserController::class, 'create'])->name('create');
    Route::post('/{tenant}', [PaginaUserController::class, 'store'])->name('store');
    Route::get('/{tenant}/{user}/edit', [PaginaUserController::class, 'edit'])->name('edit');
    Route::put('/{tenant}/{user}', [PaginaUserController::class, 'update'])->name('update');
    Route::delete('/{tenant}/{user}', [PaginaUserController::class, 'destroy'])->name('destroy');
});
Route::prefix('configuracao')->name('configuracao.')->group(function () {
    Route::get('/{tenant}', [ConfiguracaoController::class, 'detail'])->name('generate.detail');
    Route::post('/{tenant}', [ConfiguracaoController::class, 'forms'])->name('forms');
    Route::put('{tenantForm}/expires-at', [ConfiguracaoController::class, 'createExpiresAt'])->name('expires-at');
    Route::delete('{tenantForm}/unlink', [ConfiguracaoController::class, 'removeVinculo'])->name('unlink');
});