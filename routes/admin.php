<?php

use App\Http\Controllers\ReportController;
use App\Http\Controllers\SmsLogsController;
use App\Http\Controllers\TenantController;
use App\Models\Question;
use App\Models\Tenant;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Admin dashboard routes (uses master database)
|
*/

Route::prefix('admin')->name('admin.')->middleware(['web', 'auth'])->group(function () {
    Route::get('/', function () {
        $tenants = Tenant::with('questions')->paginate(10);
        $questions = Question::all();

        return Inertia::render('Dashboard', [
            'tenants' => $tenants,
            'questions' => $questions
        ]);
    })->name('dashboard');

    Route::resource('tenants', TenantController::class);
    Route::post('tenants/{tenant}/questions', [TenantController::class, 'attachQuestions'])
        ->name('tenants.attach-questions');
    Route::post('tenants/{tenant}/add-sms-quota', [TenantController::class, 'addSmsQuota'])
        ->name('tenants.add-sms-quota');

    Route::get('sms-logs', [SmsLogsController::class, 'index'])->name('sms-logs.index');
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::post('sms-logs/global-balance', [SmsLogsController::class, 'addGlobalBalance'])->name('sms-logs.add-global-balance');
});