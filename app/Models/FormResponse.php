<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormResponse extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'form_id',
        'user_id',
        'answers',
        'ip_address',
        'accepted_terms',
        'accepted_at',
    ];
    protected $casts = [
        'answers'        => 'array',
        'accepted_terms' => 'boolean',
        'accepted_at'    => 'datetime',
    ];
    protected $dates = [
        'accepted_at',
    ];
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function getHasAcceptedTermsAttribute(): bool
    {
        return $this->accepted_terms === true && $this->accepted_at !== null;
    }
}