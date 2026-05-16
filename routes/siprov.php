<?php

use App\Http\Controllers\Siprov\CreateSiprovIntegrationController;
use App\Http\Controllers\Siprov\SiprovIndexController;
use Illuminate\Support\Facades\Route;

Route::get('/', SiprovIndexController::class)->name('index');
Route::post('/store', CreateSiprovIntegrationController::class)->name('store');