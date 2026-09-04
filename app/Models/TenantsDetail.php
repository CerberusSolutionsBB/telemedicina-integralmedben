<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class TenantsDetail extends Model
{
    use SoftDeletes;

    protected $connection = 'mysql';

    protected $table = 'tenants_details';

    protected $fillable = [
        'code',
        'descricao',
        'slug',
        'form_id',
        'path_arquivos',
        'tenant_id',
        'user_id',
        'logo',
        'favicon',
        'cor_primaria',
        'cor_secundaria',
        'configuracao',
        'cartao_cor_primaria',
        'cartao_cor_secundaria',
        'cartao_cor_texto',
        'cartao_fonte',
        'cartao_logo',
        'cartao_imagem_frente',
        'cartao_imagem_verso',
    ];

    protected $casts = [
        'cor_primaria' => 'string',
        'cor_secundaria' => 'string',
        'configuracao' => 'array',
    ];

    protected $appends = [
        'sigla',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id', 'id');
    }

    public function getSiglaAttribute(): ?string
    {
        if (blank($this->descricao)) {
            return null;
        }

        $limpo = preg_replace('/[^\p{L}\p{N}]/u', '', $this->descricao);

        return Str::upper(Str::substr($limpo, 0, 3));
    }
}
