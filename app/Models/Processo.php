<?php

namespace App\Models;

use App\Models\Concerns\AuditoriaDoTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Processo do parceiro (banco do tenant): título, descrição, ícone (lucide), status,
 * valor, se usa campos personalizados (campo_status) e os serviços vinculados.
 * Criação, alteração e exclusão auditadas no banco central com a tag do tenant.
 */
class Processo extends Model implements Auditable
{
    use AuditableTrait, AuditoriaDoTenant {
        // A tag do tenant substitui as tags padrão do laravel-auditing.
        AuditoriaDoTenant::generateTags insteadof AuditableTrait;
    }

    protected $table = 'processos';

    protected $fillable = [
        'titulo',
        'descricao',
        'icone',
        'status',
        'campo_status',
        'valor',
    ];

    protected $casts = [
        'status' => 'boolean',
        'campo_status' => 'boolean',
        'valor' => 'decimal:2',
    ];

    /**
     * Valor por extenso: "R$ 150,00".
     */
    public function valorFormatado(): string
    {
        return self::moeda($this->valor);
    }

    public static function moeda(mixed $valor): string
    {
        return 'R$ '.number_format((float) $valor, 2, ',', '.');
    }

    /**
     * Serviços vinculados ao processo (gravar com auditSync para ficar no histórico).
     */
    public function servicos(): BelongsToMany
    {
        return $this->belongsToMany(Servico::class, 'processos_servicos')->withTimestamps()->orderBy('titulo');
    }

    /**
     * Usos de cupom de benefício no processo (histórico de quem usou).
     */
    public function usosCupom(): HasMany
    {
        return $this->hasMany(ProcessoCupomUso::class)->latest()->latest('id');
    }
}
