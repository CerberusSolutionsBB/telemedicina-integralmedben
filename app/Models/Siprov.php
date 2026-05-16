<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Siprov extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING    = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SUCCESS    = 'success';
    public const STATUS_FAILED     = 'failed';

    protected $fillable = [
        'user_id',
        'codigo_integracao',
        'nome_pessoa',
        'cpf_cnpj',
        'email',
        'sexo',
        'data_nascimento',
        'cod_loja',
        'cod_plano',
        'ativo',
        'dia_vencimento',
        'situacao',
        'payload_associado',
        'payload_beneficio',
        'response_associado',
        'response_beneficio',
        'status',
        'error_message',
        'integrated_at',
    ];

    protected $casts = [
        'ativo'              => 'boolean',
        'data_nascimento'    => 'date',
        'integrated_at'      => 'datetime',
        'deleted_at'         => 'datetime',

        'payload_associado'  => 'array',
        'payload_beneficio'  => 'array',
        'response_associado' => 'array',
        'response_beneficio' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING    => 'Pendente',
            self::STATUS_PROCESSING => 'Processando',
            self::STATUS_SUCCESS    => 'Sucesso',
            self::STATUS_FAILED     => 'Falhou',
            default                 => 'Desconhecido',
        };
    }

    public function getPlanoLabelAttribute(): string
    {
        return match ((int) $this->cod_plano) {
            331385  => 'Clínica Familiar',
            331384  => 'Clínica Individual',
            331386  => 'Saúde Mental',
            default => 'Plano não identificado',
        };
    }

    public function getCpfFormatadoAttribute(): string
    {
        $cpf = preg_replace('/\D/', '', $this->cpf_cnpj);

        if (strlen($cpf) !== 11) {
            return $this->cpf_cnpj;
        }

        return preg_replace(
            '/(\d{3})(\d{3})(\d{3})(\d{2})/',
            '$1.$2.$3-$4',
            $cpf
        );
    }
}