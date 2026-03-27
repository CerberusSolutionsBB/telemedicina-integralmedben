<?php

use App\Http\Controllers\Form\CreateFormController;
use App\Http\Controllers\Form\IndexFormController;
use App\Http\Controllers\Form\PublicFormController;
use App\Http\Controllers\Form\ShowFormController;
use App\Http\Controllers\Form\StoreFormController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas Públicas (sem autenticação)
|--------------------------------------------------------------------------
*/

// Rota pública para responder formulários
// URL: /forms/f/{slug}
// Name: forms.public.show
Route::get('/f/{slug}', [PublicFormController::class, 'show'])
    ->name('public.show');

Route::post('/f/{slug}', [PublicFormController::class, 'store'])
    ->name('public.store');

Route::get('/f/{slug}/obrigado', [PublicFormController::class, 'thanks'])
    ->name('public.thanks');

/*
|--------------------------------------------------------------------------
| Rotas Administrativas (com autenticação)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {

    // Listar todos os formulários
    Route::get('/', IndexFormController::class)
        ->name('index')
        ->middleware('permission:forms.view');

    // Criar novo formulário
    Route::get('/create', CreateFormController::class)
        ->name('create')
        ->middleware('permission:forms.create');

    // Salvar formulário
    Route::post('/', StoreFormController::class)
        ->name('store')
        ->middleware('permission:forms.create');

    // Visualizar formulário (admin)
    Route::get('/{form}', ShowFormController::class)
        ->name('show')
        ->middleware('permission:forms.view');

    // Editar formulário
    // Route::get('/{form}/edit', EditFormController::class)
    //     ->name('edit')
    //     ->middleware('permission:forms.edit');

    // // Atualizar formulário
    // Route::put('/{form}', UpdateFormController::class)
    //     ->name('update')
    //     ->middleware('permission:forms.edit');

    // // Excluir formulário
    // Route::delete('/{form}', DestroyFormController::class)
    //     ->name('destroy')
    //     ->middleware('permission:forms.delete');
});