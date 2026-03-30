<?php
use App\Http\Controllers\Form\CreateFormController;
use App\Http\Controllers\Form\DestroyFormController;
use App\Http\Controllers\Form\EditFormController;
use App\Http\Controllers\Form\IndexFormController;
use App\Http\Controllers\Form\PublicFormController;
use App\Http\Controllers\Form\ShowFormController;
use App\Http\Controllers\Form\StoreFormController;
use App\Http\Controllers\Form\ToggleVisibilityFormController;
use App\Http\Controllers\Form\UpdateFormController;
use App\Http\Controllers\Form\UpdateStatusFormController;
use Illuminate\Support\Facades\Route;

Route::get('/f/{slug}', [PublicFormController::class, 'show'])
    ->name('public.show');
Route::post('/f/{slug}', [PublicFormController::class, 'store'])
    ->name('public.store');
Route::get('/f/{slug}/obrigado', [PublicFormController::class, 'thanks'])
    ->name('public.thanks');
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', IndexFormController::class)
        ->name('index')
        ->middleware('permission:forms.view');
    Route::get('/create', CreateFormController::class)
        ->name('create')
        ->middleware('permission:forms.create');
    Route::post('/', StoreFormController::class)
        ->name('store')
        ->middleware('permission:forms.create');
    Route::get('/{form}', ShowFormController::class)
        ->name('show')
        ->middleware('permission:forms.view');
    Route::get('/{form}/edit', EditFormController::class)
        ->name('edit')
        ->middleware('permission:forms.edit');
    Route::put('/{form}', UpdateFormController::class)
        ->name('update')
        ->middleware('permission:forms.edit');
    Route::patch('/{form}/status', UpdateStatusFormController::class)
        ->name('update-status')
        ->middleware('permission:forms.update.status');
    Route::patch('/{form}/visibility', ToggleVisibilityFormController::class)
        ->name('toggle-visibility')
        ->middleware('permission:forms.toggle.visibility');
    Route::delete('/{form}', DestroyFormController::class)
        ->name('destroy')
        ->middleware('permission:forms.delete');
});