<?php

namespace App\Models\Concerns;

/**
 * Tag das auditorias de models do banco do tenant: ids se repetem entre tenants
 * (cada um tem seu banco), e a auditoria fica no banco central.
 */
trait AuditoriaDoTenant
{
    public function generateTags(): array
    {
        return tenant() ? ['tenant:'.tenant('id')] : [];
    }
}
