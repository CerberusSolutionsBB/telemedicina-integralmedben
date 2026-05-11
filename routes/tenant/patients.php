<?php

use App\Http\Controllers\PatientController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::prefix('cpanel')->name('cpanel.')->group(function () {
        Route::get('/patients', [PatientController::class, 'index'])
            ->name('patients.index');

        Route::get('/patients/report', [PatientController::class, 'reportPdf'])
            ->name('patients.report');

        Route::put('/patients/{patient}', [PatientController::class, 'update'])
            ->name('patients.update');

        Route::delete('/patients/{patient}', [PatientController::class, 'destroy'])
            ->name('patients.destroy');

        Route::get('/patients/{patient}', [PatientController::class, 'show'])
            ->name('patients.show');

        Route::get('/patients/{patient}/pdf', [PatientController::class, 'downloadPdf'])
            ->name('patients.pdf');

        Route::post('/patients/{patient}/resend-sms', [PatientController::class, 'resendSms'])
            ->name('patients.resend-sms');
    });
});
