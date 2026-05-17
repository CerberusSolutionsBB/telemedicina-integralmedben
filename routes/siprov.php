<?php

use App\Http\Controllers\Siprov\CreateSiprovIntegrationController;
use App\Http\Controllers\Siprov\SiprovCreateController;
use App\Http\Controllers\Siprov\SiprovIndexController;
use Illuminate\Support\Facades\Route;

Route::get('/create', SiprovCreateController::class)->name('create');
Route::get('/', SiprovIndexController::class)->name('index');
Route::post('/store', CreateSiprovIntegrationController::class)->name('store');
Route::get('/debug-siprov', function () {
    return [
        'base_url' => config('siprov.base_url'),
        'user'     => config('siprov.user'),
        'password' => config('siprov.password'),
        'cod_loja' => config('siprov.cod_loja'),
    ];
});
