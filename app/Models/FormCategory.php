<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormCategory extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'description',
        'slug',
        'sort_order',
        'color',
        'icon',
        'status',
    ];
    protected $casts = [
        'status'     => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    public function getCreatedAtFormattedAttribute(): ?string
    {
        return $this->created_at?->format('d/m/Y');
    }
    public function getCreatedAtFullAttribute(): ?string
    {
        return $this->created_at?->format('d/m/Y H:i');
    }
    public function getUpdatedAtFormattedAttribute(): ?string
    {
        return $this->updated_at?->format('d/m/Y');
    }
    public function getCreatedAtHumanAttribute(): ?string
    {
        return $this->created_at?->diffForHumans();
    }
}