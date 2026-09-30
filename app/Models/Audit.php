<?php

namespace App\Models;

use OwenIt\Auditing\Models\Audit as BaseAudit;

/**
 * Auditoria (owen-it/laravel-auditing) com o dispositivo de origem da ação.
 *
 * @property array|null $dispositivo
 */
class Audit extends BaseAudit
{
    protected $casts = [
        'old_values' => 'json',
        'new_values' => 'json',
        'dispositivo' => 'array',
    ];
}
