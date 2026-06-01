<?php

use App\Providers\AppServiceProvider;
use App\Providers\RouteModuleServiceProvider;
use App\Providers\TenancyServiceProvider;

return [
    AppServiceProvider::class,
    RouteModuleServiceProvider::class,
    TenancyServiceProvider::class,
];
