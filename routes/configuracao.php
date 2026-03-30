<?php

use App\Http\Controllers\Category\Form\CreateFormCategoryController;
use App\Http\Controllers\Category\Form\DestroyFormCategoryController;
use App\Http\Controllers\Category\Form\EditFormCategoryController;
use App\Http\Controllers\Category\Form\IndexFormCategoryController;
use App\Http\Controllers\Category\Form\ShowFormCategoryController;
use App\Http\Controllers\Category\Form\StoreFormCategoryController;
use App\Http\Controllers\Category\Form\UpdateFormCategoryController;
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