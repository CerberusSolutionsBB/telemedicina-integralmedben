<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Cupom de desconto na venda de planos. Sem tenant_id é global (admin);
 * com tenant_id só vale nas vendas daquele parceiro.
 */
class Cupom extends Model implements Auditable
{
    use AuditableTrait;

    public const TIPO_PERCENTUAL = 'percentual';

    public const TIPO_VALOR_FIXO = 'valor_fixo';

    public const TIPOS = [
        self::TIPO_PERCENTUAL => 'Percentual',
        self::TIPO_VALOR_FIXO => 'Valor fixo',
    ];

    /** Texto abaixo do desconto no PDF quando o cupom não define o seu. */
    public const TEXTO_DESCONTO_PADRAO = 'na adesão ao plano de telemedicina';

    public const STATUS_ATIVO = 'ativo';

    public const STATUS_PAUSADO = 'pausado';

    public const STATUS_CANCELADO = 'cancelado';

    public const STATUS = [
        self::STATUS_ATIVO => 'Ativo',
        self::STATUS_PAUSADO => 'Pausado',
        self::STATUS_CANCELADO => 'Cancelado',
    ];

    public const APLICAVEL_TODOS = 'todos';

    public const APLICAVEL_PLANOS = 'planos';

    public const APLICAVEL_PRIMEIRO_CONTRATO = 'primeiro_contrato';

    public const APLICAVEIS = [
        self::APLICAVEL_TODOS => 'Todos os planos',
        self::APLICAVEL_PLANOS => 'Planos específicos',
        self::APLICAVEL_PRIMEIRO_CONTRATO => 'Primeiro contrato do CPF',
    ];

    public const ORIGENS = [
        'manual' => 'Manual',
        'campanha' => 'Campanha',
        'indicacao' => 'Indicação',
        'suporte' => 'Suporte',
    ];

    protected $connection = 'mysql';

    protected $table = 'cupons';

    protected $fillable = [
        'codigo',
        'alvo',
        'tipo',
        'valor',
        'texto_desconto',
        'tenant_id',
        'criado_por_id',
        'criado_por_nome',
        'origem',
        'inicio_em',
        'expira_em',
        'limite_uso_total',
        'limite_uso_por_cpf',
        'usos',
        'status',
        'valor_minimo',
        'aplicavel_a',
        'observacoes',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'valor_minimo' => 'decimal:2',
        'inicio_em' => 'datetime',
        'expira_em' => 'datetime',
        'limite_uso_total' => 'integer',
        'limite_uso_por_cpf' => 'integer',
        'usos' => 'integer',
    ];

    /**
     * Código sempre maiúsculo e sem espaços: a busca não diferencia "promo10" de "PROMO10".
     */
    public static function normalizarCodigo(?string $codigo): string
    {
        return mb_strtoupper(preg_replace('/\s+/', '', (string) $codigo));
    }

    /**
     * Situação efetiva: expirado, agendado e esgotado são calculados (não dependem de job).
     */
    public function situacao(): string
    {
        if ($this->status !== self::STATUS_ATIVO) {
            return $this->status;
        }

        if ($this->expira_em && now()->greaterThanOrEqualTo($this->expira_em)) {
            return 'expirado';
        }

        if ($this->inicio_em && now()->lessThan($this->inicio_em)) {
            return 'agendado';
        }

        if ($this->limite_uso_total !== null && $this->usos >= $this->limite_uso_total) {
            return 'esgotado';
        }

        return self::STATUS_ATIVO;
    }

    /**
     * Desconto sobre o valor do plano; nunca maior que o próprio valor.
     */
    public function descontoSobre(float $valor): float
    {
        $desconto = $this->tipo === self::TIPO_PERCENTUAL
            ? $valor * ((float) $this->valor / 100)
            : (float) $this->valor;

        return round(min($valor, max(0, $desconto)), 2);
    }

    /**
     * Cupons visíveis para o tenant: só os dele (os globais são do admin).
     */
    public function scopeDoTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    public function planos(): HasMany
    {
        return $this->hasMany(CupomPlano::class);
    }

    public function usosRegistrados(): HasMany
    {
        return $this->hasMany(CupomUso::class);
    }

    /**
     * Mesma tag dos registros do tenant: entra no histórico de auditoria dele.
     */
    public function generateTags(): array
    {
        return $this->tenant_id ? ['tenant:'.$this->tenant_id] : [];
    }
}
