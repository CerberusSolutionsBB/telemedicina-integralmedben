<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role;

/**
 * Meta de desempenho dos usuários do tenant (banco do tenant).
 */
class Desempenho extends Model
{
    public const FUNCAO_REGISTRO_BENEFICIARIO_PLANO = 'registro_beneficiario_plano';

    public const FUNCAO_COMISSAO_VENDA_PLANO = 'comissao_venda_plano';

    public const FUNCOES = [
        self::FUNCAO_REGISTRO_BENEFICIARIO_PLANO => 'Registro de beneficiário por plano',
        self::FUNCAO_COMISSAO_VENDA_PLANO => 'Comissão por venda de plano',
    ];

    public const ESCOPO_TODOS = 'todos';

    public const ESCOPO_PLANO = 'plano';

    public const TIPO_INDIVIDUAL = 'individual';

    public const TIPO_COLETIVA = 'coletiva';

    public const TIPOS = [
        self::TIPO_INDIVIDUAL => 'Individual',
        self::TIPO_COLETIVA => 'Coletiva',
    ];

    // Limites de caracteres (validação e contadores do formulário).
    public const LIMITES = [
        'titulo' => 255,
        'descricao' => 2000,
    ];

    protected $fillable = [
        'titulo',
        'descricao',
        'funcao',
        'escopo_plano',
        'cod_plano',
        'tipo_meta',
        'meta',
        'meta_valor',
        'data_inicio',
        'prazo',
        'user_id',
    ];

    protected $casts = [
        'meta' => 'integer',
        'meta_valor' => 'decimal:2',
        'data_inicio' => 'date:Y-m-d',
        'prazo' => 'date:Y-m-d',
    ];

    /**
     * Meta financeira (comissão por venda): acumula R$ além da quantidade.
     */
    public function ehComissao(): bool
    {
        return $this->funcao === self::FUNCAO_COMISSAO_VENDA_PLANO;
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'desempenho_role');
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'desempenho_user');
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
