<?php

use App\Http\Controllers\CentralUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Financeiro\CupomController;
use App\Http\Controllers\FinanceiroController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\SmsTemplateController;
use App\Http\Controllers\TenantController;
use App\Http\Middleware\PreventAccessFromTenantDomains;
use Illuminate\Support\Facades\Route;

Route::middleware([PreventAccessFromTenantDomains::class])->group(function () {

    // Antes: redirect direto — Agora: LandingPage para visitantes, redirect para autenticados
    Route::get('/', [LandingController::class, 'index']);

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware(['auth', 'verified'])
        ->name('dashboard');

    Route::get('/financeiro', [FinanceiroController::class, 'index'])
        ->middleware(['auth', 'verified', 'permission:financeiro.view'])
        ->name('financeiro.index');
    Route::permanentRedirect('/financiero', '/financeiro');

    Route::middleware('auth')->group(function () {
        Route::get('/credenciados', [TenantController::class, 'index'])->name('credenciados.index');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        Route::get('/registros', [QuestionController::class, 'index'])->name('registros.index');
        Route::post('/questions', [QuestionController::class, 'store'])->name('questions.store');
        Route::get('/questions/{question}', [QuestionController::class, 'show'])->name('questions.show');
        Route::put('/questions/{question}', [QuestionController::class, 'update'])->name('questions.update');
        Route::delete('/questions/{question}', [QuestionController::class, 'destroy'])->name('questions.destroy');

        Route::post('/tenant_questions', [QuestionController::class, 'storeTenantQuestions'])->name('tenant_questions.store');

        Route::get('/central-users', [CentralUserController::class, 'index'])->middleware('permission:users.view')->name('central-users.index');
        Route::post('/central-users', [CentralUserController::class, 'store'])->middleware('permission:users.create')->name('central-users.store');
        Route::put('/central-users/{user}', [CentralUserController::class, 'update'])->middleware('permission:users.edit')->name('central-users.update');
        Route::delete('/central-users/{user}', [CentralUserController::class, 'destroy'])->middleware('permission:users.delete')->name('central-users.destroy');

        Route::middleware('permission:sms-templates.manage')->group(function () {
            Route::get('/sms-templates', [SmsTemplateController::class, 'index'])->name('sms-templates.index');
            Route::post('/sms-templates', [SmsTemplateController::class, 'store'])->name('sms-templates.store');
            Route::put('/sms-templates/{smsTemplate}', [SmsTemplateController::class, 'update'])->name('sms-templates.update');
            Route::delete('/sms-templates/{smsTemplate}', [SmsTemplateController::class, 'destroy'])->name('sms-templates.destroy');
            Route::post('/sms-templates/{smsTemplate}/tenants', [SmsTemplateController::class, 'assignTenants'])->name('sms-templates.assign-tenants');
        });

        Route::middleware('permission:cupons.manage')->prefix('financeiro/cupons')->name('financeiro.cupons.')->group(function () {
            Route::get('/', [CupomController::class, 'index'])->name('index');
            Route::get('/create', [CupomController::class, 'create'])->name('create');
            Route::post('/', [CupomController::class, 'store'])->name('store');
            Route::get('/{cupom}', [CupomController::class, 'show'])->name('show');
            Route::get('/{cupom}/edit', [CupomController::class, 'edit'])->name('edit');
            Route::put('/{cupom}', [CupomController::class, 'update'])->name('update');
            Route::put('/{cupom}/status', [CupomController::class, 'status'])->name('status');
            Route::get('/{cupom}/pdf', [CupomController::class, 'pdf'])->name('pdf');
            Route::delete('/{cupom}', [CupomController::class, 'destroy'])->name('destroy');
        });

        Route::post('/store', [TenantController::class, 'store'])->middleware('permission:paginas.create')->name('tenants.store');
        Route::put('/tenants/{tenant}', [TenantController::class, 'update'])->middleware('permission:paginas.edit')->name('tenants.update');
        Route::delete('/delete', [TenantController::class, 'destroy'])->middleware('permission:paginas.delete')->name('tenants.destroy');
    });
});

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
