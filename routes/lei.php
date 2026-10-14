<?php

use App\Http\Controllers\Lei\CreateLeiController;
use App\Http\Controllers\Lei\DestroyLeiController;
use App\Http\Controllers\Lei\EditLeiController;
use App\Http\Controllers\Lei\IndexLeiController;
use App\Http\Controllers\Lei\ShowLeiController;
use App\Http\Controllers\Lei\StoreLeiController;
use App\Http\Controllers\Lei\UpdateLeiController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:leis.view')->group(function () {
    Route::get('/', IndexLeiController::class)->name('index');
});

Route::middleware('permission:leis.create')->group(function () {
    Route::get('/create', CreateLeiController::class)->name('create');
    Route::post('/', StoreLeiController::class)->name('store');
});

Route::middleware('permission:leis.edit')->group(function () {
    Route::get('{lei}/edit', EditLeiController::class)->name('edit');
    Route::put('/{lei}', UpdateLeiController::class)->name('update');
});

Route::middleware('permission:leis.delete')->delete('/{lei}', DestroyLeiController::class)->name('destroy');

Route::middleware('permission:leis.view')->get('/{lei}', ShowLeiController::class)->name('show');
