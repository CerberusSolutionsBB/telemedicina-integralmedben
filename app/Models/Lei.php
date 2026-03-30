<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lei extends Model
{
    use SoftDeletes;

    public const TIPO_LGPD                 = 'lgpd';
    public const TIPO_CONSOLIDADA          = 'consolidada';
    public const TIPO_CODIGO_ETICA         = 'codigo_etica';
    public const TIPO_POLITICA_PRIVACIDADE = 'politica_privacidade';
    public const TIPO_TERMO_USO            = 'termo_uso';
    public const TIPO_OUTRO                = 'outro';

    public const TIPOS_VALIDOS = [
        self::TIPO_LGPD,
        self::TIPO_CONSOLIDADA,
        self::TIPO_CODIGO_ETICA,
        self::TIPO_POLITICA_PRIVACIDADE,
        self::TIPO_TERMO_USO,
        self::TIPO_OUTRO,
    ];

    public const TIPOS_LABELS = [
        self::TIPO_LGPD                 => 'LGPD (Lei Geral de Proteção de Dados)',
        self::TIPO_CONSOLIDADA          => 'Normas Consolidadas',
        self::TIPO_CODIGO_ETICA         => 'Código de Ética',
        self::TIPO_POLITICA_PRIVACIDADE => 'Política de Privacidade',
        self::TIPO_TERMO_USO            => 'Termo de Uso',
        self::TIPO_OUTRO                => 'Outro',
    ];

    protected $fillable = [
        'user_id',
        'title',
        'text',
        'type',
        'status',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($model): void {
            if (! in_array($model->type, self::TIPOS_VALIDOS)) {
                throw new \InvalidArgumentException("Tipo '{$model->type}' inválido.");
            }
        });
    }

    /**
     * Usuário criador da lei
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Formulários associados a esta lei
     */
    public function forms(): HasMany
    {
        return $this->hasMany(Form::class, 'lei_id');
    }

    /**
     * Scope: Apenas LGPD
     */
    public function scopeLgpd($query)
    {
        return $query->where('type', self::TIPO_LGPD);
    }

    /**
     * Scope: Filtrar por tipo
     */
    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('type', $tipo);
    }

    /**
     * Accessor: Label do tipo
     */
    public function getTipoLabelAttribute(): string
    {
        return self::TIPOS_LABELS[$this->type] ?? $this->type;
    }
}