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

    public const FUNCOES = [
        self::FUNCAO_REGISTRO_BENEFICIARIO_PLANO => 'Registro de beneficiário por plano',
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
        'data_inicio',
        'prazo',
        'user_id',
    ];

    protected $casts = [
        'meta' => 'integer',
        'data_inicio' => 'date:Y-m-d',
        'prazo' => 'date:Y-m-d',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'desempenho_role');
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
