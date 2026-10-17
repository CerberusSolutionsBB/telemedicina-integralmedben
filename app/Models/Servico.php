<?php

namespace App\Models;

use App\Models\Concerns\AuditoriaDoTenant;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Serviço oferecido pelo parceiro (banco do tenant): título, descrição, ícone (lucide) e status.
 * Criação, alteração e exclusão auditadas no banco central com a tag do tenant.
 */
class Servico extends Model implements Auditable
{
    use AuditableTrait, AuditoriaDoTenant {
        // A tag do tenant substitui as tags padrão do laravel-auditing.
        AuditoriaDoTenant::generateTags insteadof AuditableTrait;
    }

    protected $table = 'servicos';

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
