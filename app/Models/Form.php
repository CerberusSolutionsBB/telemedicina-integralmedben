<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Form extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'mysql';

    public const STATUS_RASCUNHO = 'rascunho';

    public const STATUS_ATIVO = 'ativo';

    public const STATUS_PAUSADO = 'pausado';

    public const STATUS_ENCERRADO = 'encerrado';

    protected $table = 'forms';

    protected $fillable = [
        'code',
        'categoria_id',
        'credencia_cluble_id',
        'lei_id',
        'user_id',
        'title',
        'slug',
        'description',
        'primary_color',
        'secondary_color',
        'published_at',
        'expires_at',
        'response_limit',
        'is_public',
        'settings',
        'responses_count',
        'btn_confirmar_descricao',
        'sub_descricao',
        'observacao',
        'status',
    ];

    protected $appends = [
        'logo_url',
        'expires_at_br',
        'created_at_br',
        'updated_at_br',
        'atual_at_br',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'settings' => 'array',
        'responses_count' => 'integer',
        'primary_color' => 'string',
        'secondary_color' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function getAtualAtBrAttribute()
    {
        return now()->format('d/m/Y H:i');
    }

    public function getCreatedAtBrAttribute()
    {
        return $this->created_at ? $this->created_at->format('d/m/Y H:i') : null;
    }

    public function getUpdatedAtBrAttribute()
    {
        return $this->updated_at ? $this->updated_at->format('d/m/Y H:i') : null;
    }

    public function getExpiresAtBrAttribute()
    {
        return $this->expires_at ? $this->expires_at->format('d/m/Y H:i') : null;
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $logoArquivo = $this->arquivos()
                    ->wherePivot('tipo', 'logo')
                    ->first();
                if ($logoArquivo && $logoArquivo->path) {
                    return asset('storage/'.$logoArquivo->path);
                }

                return null;
            },
        );
    }

    public function getLogosAttribute()
    {
        return $this->arquivos()
            ->wherePivot('tipo', 'logo')
            ->get()
            ->map(fn ($arq) => [
                'id' => $arq->id,
                'url' => asset('storage/'.$arq->path),
                'nome' => $arq->nome,
                'tipo' => $arq->pivot->tipo,
                'posicao' => $arq->pivot->posicao,
            ]);
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model): void {
            if (empty($model->code)) {
                $model->code = (string) Str::ulid();
            }
            if (empty($model->slug) && ! empty($model->title)) {
                $model->slug = Str::slug($model->title);
            }
            $model->responses_count ??= 0;
            $model->primary_color ??= '#3B82F6';
            $model->secondary_color ??= '#10B981';
        });
        static::updating(function (self $model): void {
            if ($model->isDirty('title') && ! $model->isDirty('slug')) {
                $model->slug = Str::slug($model->title);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(FormCategory::class, 'categoria_id');
    }

    public function lei(): BelongsTo
    {
        return $this->belongsTo(Lei::class, 'lei_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('order');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(FormResponse::class);
    }

    public function getColorsAttribute(): array
    {
        return [
            'primary' => $this->primary_color ?? '#22d3ee',
            'secondary' => $this->secondary_color ?? '#06b6d4',
        ];
    }

    public function arquivos()
    {
        return $this->belongsToMany(Arquivo::class, 'form_arquivos', 'form_id', 'arquivo_id');
    }

    public function credenciaCluble(): BelongsTo
    {
        return $this->belongsTo(CredenciasCluble::class, 'credencia_cluble_id');
    }

    public function tenants()
    {
        return $this->belongsToMany(
            Tenant::class,
            'tenants_forms',
            'form_id',
            'tenant_id'
        )
            ->withTimestamps();
    }
}
