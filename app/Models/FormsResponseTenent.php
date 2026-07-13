<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class FormsResponseTenent extends Model
{
    use SoftDeletes;

    protected $connection = 'mysql';

    protected $table = 'forms_response_tenents';

    protected $fillable = [
        'code',
        'tenant_id',
        'form_id',
        'response_id',
        'status_paciente',
    ];

    /**
     * Auto gerar código
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->code)) {
                $model->code = strtoupper(Str::random(8));
            }
        });
    }

    /**
     * Relacionamento com Tenant
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relacionamento com Form
     */
    public function form()
    {
        return $this->belongsTo(Form::class);
    }
}
