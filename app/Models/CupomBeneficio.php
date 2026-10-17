<?php

namespace App\Models;

use App\Models\Concerns\AuditoriaDoTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Cupom de benefício do parceiro (banco do tenant): desconto (percentual ou valor fixo)
 * que o beneficiário usa num convênio ou serviço, opcionalmente só para um plano, até
 * o prazo. Não confundir com Cupom (desconto na venda do plano, banco central).
 */
class CupomBeneficio extends Model implements Auditable
{
    use AuditableTrait, AuditoriaDoTenant {
        // A tag do tenant substitui as tags padrão do laravel-auditing.
        AuditoriaDoTenant::generateTags insteadof AuditableTrait;
    }

    public const TIPO_PERCENTUAL = 'percentual';

    public const TIPO_VALOR_FIXO = 'valor_fixo';

    public const TIPOS = [
        self::TIPO_PERCENTUAL => 'Percentual',
        self::TIPO_VALOR_FIXO => 'Valor fixo',
    ];

    protected $table = 'cupom_beneficios';

    /** Caracteres do código gerado: sem os que se confundem (0/O, 1/I/L). */
    public const ALFABETO_CODIGO = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public const TAMANHO_CODIGO = 8;

    protected $fillable = [
        'codigo',
        'titulo',
        'descricao',
        'icone',
        'status',
        'cod_plano',
        'convenio_id',
        'servico_id',
        'tipo_desconto',
        'valor_desconto',
        'prazo',
    ];

    protected $casts = [
        'status' => 'boolean',
        'valor_desconto' => 'decimal:2',
        'prazo' => 'date:Y-m-d',
    ];

    /**
     * Sem código informado, o cupom recebe um gerado ao ser criado.
     */
    protected static function booted(): void
    {
        static::creating(function (CupomBeneficio $cupom) {
            $cupom->codigo = filled($cupom->codigo) ? self::normalizarCodigo($cupom->codigo) : self::gerarCodigo();
        });
    }

    /**
     * Código aleatório e único no banco do parceiro (ex.: K7M3QX9P).
     */
    public static function gerarCodigo(): string
    {
        do {
            $codigo = collect(range(1, self::TAMANHO_CODIGO))
                ->map(fn () => self::ALFABETO_CODIGO[random_int(0, strlen(self::ALFABETO_CODIGO) - 1)])
                ->implode('');
        } while (static::query()->where('codigo', $codigo)->exists());

        return $codigo;
    }

    /**
     * Maiúsculas e sem espaços: "farmacia 15" e "FARMACIA15" são o mesmo código.
     */
    public static function normalizarCodigo(?string $codigo): string
    {
        return mb_strtoupper(preg_replace('/\s+/', '', (string) $codigo));
    }

    /**
     * Passou do prazo (o dia do prazo ainda vale).
     */
    public function vencido(): bool
    {
        return $this->prazo !== null && $this->prazo->endOfDay()->isPast();
    }

    /**
     * Desconto por extenso: "15%" ou "R$ 30,00".
     */
    public function descontoFormatado(): string
    {
        return $this->tipo_desconto === self::TIPO_VALOR_FIXO
            ? 'R$ '.number_format((float) $this->valor_desconto, 2, ',', '.')
            : rtrim(rtrim(number_format((float) $this->valor_desconto, 2, ',', '.'), '0'), ',').'%';
    }

    public function convenio(): BelongsTo
    {
        return $this->belongsTo(Convenio::class);
    }

    public function servico(): BelongsTo
    {
        return $this->belongsTo(Servico::class);
    }
}
