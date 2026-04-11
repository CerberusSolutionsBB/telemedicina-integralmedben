<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Form extends Model
{
    use HasFactory, SoftDeletes;
    public const STATUS_RASCUNHO  = 'rascunho';
    public const STATUS_ATIVO     = 'ativo';
    public const STATUS_PAUSADO   = 'pausado';
    public const STATUS_ENCERRADO = 'encerrado';
    protected $table              = 'forms';
    protected $fillable           = [
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
    protected $appends = ['logo_url'];
    protected $casts   = [
        'is_public'       => 'boolean',
        'published_at'    => 'datetime',
        'expires_at'      => 'datetime',
        'settings'        => 'array',
        'responses_count' => 'integer',
        'primary_color'   => 'string',
        'secondary_color' => 'string',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
        'deleted_at'      => 'datetime',
    ];
    protected function logoUrl(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function () {
                $logoArquivo = $this->arquivos()
                    ->wherePivot('tipo', 'logo')
                    ->first();
                if ($logoArquivo && $logoArquivo->path) {
                    return asset('storage/' . $logoArquivo->path);
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
            ->map(fn($arq) => [
                'id'      => $arq->id,
                'url'     => asset('storage/' . $arq->path),
                'nome'    => $arq->nome,
                'tipo'    => $arq->pivot->tipo,
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
            'primary'   => $this->primary_color ?? '#22d3ee',
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
}