<?php

use App\Http\Controllers\Siprov\CreateSiprovIntegrationController;
use App\Http\Controllers\Siprov\SiprovCartaoController;
use App\Http\Controllers\Siprov\SiprovCancelBeneficioController;
use App\Http\Controllers\Siprov\SiprovCreateController;
use App\Http\Controllers\Siprov\SiprovDestroyController;
use App\Http\Controllers\Siprov\SiprovIndexController;
use Illuminate\Support\Facades\Route;

Route::get('/', SiprovIndexController::class)
    ->middleware('permission:siprov.view')
    ->name('index');

Route::get('/create', SiprovCreateController::class)
    ->middleware('permission:siprov.create')
    ->name('create');

Route::post('/store', CreateSiprovIntegrationController::class)
    ->middleware('permission:siprov.create')
    ->name('store');

Route::delete('/{siprov}', SiprovDestroyController::class)
    ->middleware('permission:siprov.delete')
    ->name('destroy');

Route::put('/{codBeneficio}/cancelar', SiprovCancelBeneficioController::class)
    ->middleware('permission:siprov.delete')
    ->name('cancelar-beneficio');

Route::get('/cartao', SiprovCartaoController::class)
    ->middleware('permission:siprov.view')
    ->name('cartao');

Route::get('/debug-siprov', function () {
    return [
        'base_url' => config('siprov.base_url'),
        'user' => config('siprov.user'),
        'password' => config('siprov.password'),
        'cod_loja' => config('siprov.cod_loja'),
    ];
});
