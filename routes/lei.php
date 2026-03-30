<?php

use App\Http\Controllers\Lei\CreateLeiController;
use App\Http\Controllers\Lei\DestroyLeiController;
use App\Http\Controllers\Lei\EditLeiController;
use App\Http\Controllers\Lei\IndexLeiController;
use App\Http\Controllers\Lei\ShowLeiController;
use App\Http\Controllers\Lei\StoreLeiController;
use App\Http\Controllers\Lei\UpdateLeiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/create', CreateLeiController::class)->name('create');
    Route::get('/', IndexLeiController::class)->name('index');
    Route::post('/', StoreLeiController::class)->name('store');
    Route::get('/{lei}', ShowLeiController::class)->name('show');
    Route::get('{lei}/edit', EditLeiController::class)->name('edit');
    Route::delete('/{lei}', DestroyLeiController::class)->name('destroy');
    Route::put('/{lei}', UpdateLeiController::class)->name('update');
});