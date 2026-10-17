<?php

namespace App\Models;

use App\Models\Concerns\AuditoriaDoTenant;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Convênio do parceiro (banco do tenant): título, descrição, ícone (lucide) e status.
 * Criação, alteração e exclusão auditadas no banco central com a tag do tenant.
 */
class Convenio extends Model implements Auditable
{
    use AuditableTrait, AuditoriaDoTenant {
        // A tag do tenant substitui as tags padrão do laravel-auditing.
        AuditoriaDoTenant::generateTags insteadof AuditableTrait;
    }

    protected $table = 'convenios';

    protected $fillable = [
        'titulo',
        'descricao',
        'icone',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];
}
