<?php

use App\Http\Controllers\Category\Form\CreateFormCategoryController;
use App\Http\Controllers\Category\Form\DestroyFormCategoryController;
use App\Http\Controllers\Category\Form\EditFormCategoryController;
use App\Http\Controllers\Category\Form\IndexFormCategoryController;
use App\Http\Controllers\Category\Form\ShowFormCategoryController;
use App\Http\Controllers\Category\Form\StoreFormCategoryController;
use App\Http\Controllers\Category\Form\UpdateFormCategoryController;
use App\Http\Controllers\CredenciasCluble\CreateCredenciasCluble;
use App\Http\Controllers\CredenciasCluble\DestroyCredenciasClubleController;
use App\Http\Controllers\CredenciasCluble\EditCredenciasClubleController;
use App\Http\Controllers\CredenciasCluble\IndexCredenciasClubleController;
use App\Http\Controllers\CredenciasCluble\RefreshCredenciasCluble;
use App\Http\Controllers\CredenciasCluble\StoreCredenciasClubleController;
use App\Http\Controllers\CredenciasCluble\UpdateCredenciasClubleController;
use Illuminate\Support\Facades\Route;

Route::prefix('categories')->name('categories.')->group(function () {
    Route::prefix('forms')->name('forms.')->group(function () {
        Route::get('/', IndexFormCategoryController::class)->name('index');
        Route::get('/create', CreateFormCategoryController::class)->name('create');
        Route::get('/{formCategory}/edit', EditFormCategoryController::class)->name('edit');
        Route::post('/', StoreFormCategoryController::class)->name('store');
        Route::get('/{formCategory}', ShowFormCategoryController::class)->name('show');
        Route::put('/{formCategory}', UpdateFormCategoryController::class)->name('update');
        Route::delete('/{formCategory}', DestroyFormCategoryController::class)->name('destroy');
    });
});

Route::prefix('credencias_cluble')->name('credencias_cluble.')->group(function () {
    Route::get('/create', CreateCredenciasCluble::class)->name('create');
    Route::get('/', IndexCredenciasClubleController::class)->name('index');
    Route::post('/', StoreCredenciasClubleController::class)->name('store');
    Route::get('{credencias_cluble}/edit', EditCredenciasClubleController::class)
        ->name('edit');
    Route::delete('{id}', DestroyCredenciasClubleController::class)
        ->name('destroy');
    Route::put('{id}', UpdateCredenciasClubleController::class)
        ->name('update');
    Route::post('/{credencial}/refresh', RefreshCredenciasCluble::class)->name('refresh');

});