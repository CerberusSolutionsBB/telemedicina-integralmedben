<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/**
 * Load all Tenant route modules.
 */
Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])
    ->group(function () {

        foreach (File::glob(base_path('routes/tenant/*.php')) as $file) {
            require $file;
        }
    });
