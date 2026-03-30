<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    protected $table = 'forms';

    protected $fillable = [
        'code',
        'categoria_id',
        'lei_id',
        'user_id',
        'title',
        'slug',
        'description',
        'published_at',
        'expires_at',
        'response_limit',
        'is_public',
        'settings',
        'responses_count',
        'status',
    ];

    protected $casts = [
        'is_public'       => 'boolean',
        'published_at'    => 'datetime',
        'expires_at'      => 'datetime',
        'settings'        => 'array',
        'responses_count' => 'integer',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
        'deleted_at'      => 'datetime',
    ];

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
        });

        static::updating(function (self $model): void {
            if ($model->isDirty('title') && ! $model->isDirty('slug')) {
                $model->slug = Str::slug($model->title);
            }
        });
    }

    /**
     * Usuário criador do formulário
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Categoria do formulário
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(FormCategory::class, 'categoria_id');
    }

    /**
     * Lei associada ao formulário
     */
    public function lei(): BelongsTo
    {
        return $this->belongsTo(Lei::class, 'lei_id');
    }

    /**
     * Campos do formulário
     */
    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('order');
    }

    /**
     * Respostas do formulário
     */
    public function responses(): HasMany
    {
        return $this->hasMany(FormResponse::class);
    }
}