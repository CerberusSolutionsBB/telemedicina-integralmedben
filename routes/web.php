<?php

use App\Http\Controllers\CentralUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\SmsTemplateController;
use App\Http\Controllers\TenantController;
use App\Http\Middleware\PreventAccessFromTenantDomains;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware([PreventAccessFromTenantDomains::class])->group(function () {
    Route::get('/', function () {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return redirect()->route('login');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware(['auth', 'verified'])
        ->name('dashboard');

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

        Route::get('/central-users', [CentralUserController::class, 'index'])->name('central-users.index');
        Route::post('/central-users', [CentralUserController::class, 'store'])->name('central-users.store');
        Route::put('/central-users/{user}', [CentralUserController::class, 'update'])->name('central-users.update');
        Route::delete('/central-users/{user}', [CentralUserController::class, 'destroy'])->name('central-users.destroy');

        Route::get('/sms-templates', [SmsTemplateController::class, 'index'])->name('sms-templates.index');
        Route::post('/sms-templates', [SmsTemplateController::class, 'store'])->name('sms-templates.store');
        Route::put('/sms-templates/{smsTemplate}', [SmsTemplateController::class, 'update'])->name('sms-templates.update');
        Route::delete('/sms-templates/{smsTemplate}', [SmsTemplateController::class, 'destroy'])->name('sms-templates.destroy');
        Route::post('/sms-templates/{smsTemplate}/tenants', [SmsTemplateController::class, 'assignTenants'])->name('sms-templates.assign-tenants');
    });

    Route::post('/store', [TenantController::class, 'store'])->name('tenants.store');
    Route::put('/tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
    Route::delete('/delete', [TenantController::class, 'destroy'])->name('tenants.destroy');
});

require __DIR__ . '/auth.php';
require __DIR__ . '/admin.php';
