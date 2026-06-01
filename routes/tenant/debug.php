<?php

use Illuminate\Support\Facades\Route;

Route::get('/debug-tenant', function () {
    dd([
        'initialized' => tenancy()->initialized,
        'tenant_id' => tenant('id'),
        'tenant' => tenant(),
    ]);
});
