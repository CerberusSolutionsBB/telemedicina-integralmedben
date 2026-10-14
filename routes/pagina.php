<?php

use App\Http\Controllers\Pagina\PaginaBeneficiarioController;
use App\Http\Controllers\Pagina\PaginaCartaoDinamicoController;
use App\Http\Controllers\Pagina\PaginaCorController;
use App\Http\Controllers\Pagina\PaginaCreateController;
use App\Http\Controllers\Pagina\PaginaDestroyController;
use App\Http\Controllers\Pagina\PaginaIndexController;
use App\Http\Controllers\Pagina\PaginaLogoController;
use App\Http\Controllers\Pagina\PaginaPlanoController;
use App\Http\Controllers\Pagina\PaginaShowController;
use App\Http\Controllers\Pagina\PaginaStatusController;
use App\Http\Controllers\Pagina\PaginaStoreController;
use App\Http\Controllers\Pagina\PaginaUserController;
use App\Http\Controllers\Tenant\Configuracao\ConfiguracaoController;
use Illuminate\Support\Facades\Route;

// Imagens públicas: logo e cartão aparecem na página pública do parceiro e no painel do tenant.
Route::withoutMiddleware('auth')->group(function () {
    Route::get('configuracao/{tenant}/logo', [PaginaLogoController::class, 'show'])->name('configuracao.logo.show');
    Route::get('configuracao/{tenant}/cartao-dinamico/{tipo}', [PaginaCartaoDinamicoController::class, 'showImagem'])->whereIn('tipo', ['logo', 'frente', 'verso'])->name('configuracao.cartao-dinamico.imagem.show');
});

Route::middleware('permission:paginas.view')->get('/', PaginaIndexController::class)->name('index');

Route::middleware('permission:paginas.create')->group(function () {
    Route::get('create', PaginaCreateController::class)->name('create');
    Route::post('store', PaginaStoreController::class)->name('store');
});

Route::middleware('permission:paginas.delete')->delete('/{tenant}', PaginaDestroyController::class)->name('destroy');

Route::middleware('permission:paginas.edit')->group(function () {
    Route::put('/bulk/disable', [PaginaStatusController::class, 'bulkDisable'])->name('bulk.disable');
    Route::put('/{tenant}/status', PaginaStatusController::class)->name('status');
    Route::put('/{tenant}/cor', PaginaCorController::class)->name('cor');

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
        Route::put('/{tenant}/status-formulario-dinamico', [ConfiguracaoController::class, 'toggleStatusFormularioDinamico'])->name('status-formulario-dinamico');
        Route::put('/{tenant}/cartao-paciente', [ConfiguracaoController::class, 'toggleCartaoPaciente'])->name('cartao-paciente');
        Route::put('/{tenant}/telemedicina', [ConfiguracaoController::class, 'syncTelemedicina'])->name('telemedicina');
        Route::delete('/{tenant}/telemedicina/{telemedicinaTenant}', [ConfiguracaoController::class, 'unlinkTelemedicina'])->name('telemedicina.unlink');
        Route::put('/{tenant}/beneficiario', [PaginaBeneficiarioController::class, 'update'])->name('beneficiario');
        Route::put('/{tenant}/planos', [PaginaPlanoController::class, 'sync'])->name('planos');
        Route::put('/{tenant}/planos/{codPlano}/zerar', [PaginaPlanoController::class, 'zerar'])->name('planos.zerar');
        Route::get('/siprov/search', [ConfiguracaoController::class, 'searchSiprov'])->name('telemedicina.searchSiprov');
        Route::post('/{tenant}/logo', [PaginaLogoController::class, 'store'])->name('logo.store');
        Route::delete('/{tenant}/logo', [PaginaLogoController::class, 'destroy'])->name('logo.destroy');
        Route::put('/{tenant}/cartao-dinamico/cores', [PaginaCartaoDinamicoController::class, 'updateCores'])->name('cartao-dinamico.cores');
        Route::put('/{tenant}/cartao-dinamico/habilitar', [PaginaCartaoDinamicoController::class, 'toggleEnabled'])->name('cartao-dinamico.toggle');
        Route::put('/{tenant}/cartao-dinamico/qrcode', [PaginaCartaoDinamicoController::class, 'updateQrCode'])->name('cartao-dinamico.qrcode');
        Route::post('/{tenant}/cartao-dinamico/{tipo}', [PaginaCartaoDinamicoController::class, 'storeImagem'])->whereIn('tipo', ['logo', 'frente', 'verso'])->name('cartao-dinamico.imagem.store');
        Route::delete('/{tenant}/cartao-dinamico/{tipo}', [PaginaCartaoDinamicoController::class, 'destroyImagem'])->whereIn('tipo', ['logo', 'frente', 'verso'])->name('cartao-dinamico.imagem.destroy');
    });
});

// Por último: '/{tenant}' não pode capturar 'create' nem os prefixos acima.
Route::middleware('permission:paginas.show')->get('/{tenant}', PaginaShowController::class)->name('show');
