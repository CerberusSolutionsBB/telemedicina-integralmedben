<?php

declare(strict_types=1);

use App\Http\Controllers\PatientController;
use App\Http\Controllers\PublicFormController;
use App\Http\Controllers\Tenant\Configuracao\ConfiguracaoController;
use App\Http\Controllers\Tenant\Form\FormIndexController;
use App\Http\Controllers\Tenant\Form\FormShowController;
use App\Http\Controllers\Tenant\TenantAuthController;
use App\Http\Controllers\Tenant\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    Route::get('/', function () {

        if (Auth::check()) {
            return redirect()->route('patients.index');
        }

        return redirect()->route('tenant.login');
    });

    Route::get('/public-form', [PublicFormController::class, 'showLoginForm'])->name('public_form.store');
    Route::get('/admin/login', [TenantAuthController::class, 'showLoginForm'])->name('tenant.login');
    Route::post('/admin/login', [TenantAuthController::class, 'login']);

    Route::middleware('auth')->group(function () {
        Route::post('/admin/logout', [TenantAuthController::class, 'logout'])->name('tenant.logout');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
        Route::get('/patients/create', [PatientController::class, 'create'])->name('patients.create');
        Route::post('/patients', [PatientController::class, 'store'])->name('patients.store');
        Route::get('/patients/report', [PatientController::class, 'reportPdf'])->name('patients.report');
        Route::get('/patients/export/{format}', [PatientController::class, 'export'])->name('patients.export')->where('format', 'csv|xlsx');
        Route::get('/patients/template/{format}', [PatientController::class, 'template'])->name('patients.template')->where('format', 'csv|xlsx');
        Route::post('/patients/import', [PatientController::class, 'import'])->name('patients.import');
        Route::get('/patients/{patient}/edit', [PatientController::class, 'edit'])->name('patients.edit');
        Route::put('/patients/{patient}', [PatientController::class, 'update'])->name('patients.update');
        Route::delete('/patients/{patient}', [PatientController::class, 'destroy'])->name('patients.destroy');
        Route::get('/patients/{patient}', [PatientController::class, 'show'])->name('patients.show');
        Route::get('/patients/{patient}/pdf', [PatientController::class, 'downloadPdf'])->name('patients.pdf');
        Route::post('/patients/{patient}/resend-sms', [PatientController::class, 'resendSms'])->name('patients.resend-sms');
        Route::post('/patients/{patient}/sms-logs/{smsLog}/resend', [PatientController::class, 'resendSmsLog'])->name('patients.sms-logs.resend');
        Route::patch('/patients/{patient}/toggle-status', [PatientController::class, 'toggleStatus'])->name('patients.toggle-status');

        Route::prefix('meus-formularios')->name('meus-formularios.')->group(function () {
            Route::get('/', FormIndexController::class)->name('index');
            Route::get('/{form}', FormShowController::class)->name('show');
        });

        Route::prefix('configuracao')
            ->name('configuracao.')
            ->controller(ConfiguracaoController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/logo', [ConfiguracaoController::class, 'updateLogo'])->name('logo.update');
            });
    });
});

Route::middleware('tenant')->get('/teste', function () {
    return [
        'host' => request()->getHost(),
        'tenant' => tenant('id'),
        'db' => config('database.connections.tenant.database'),
    ];
});
