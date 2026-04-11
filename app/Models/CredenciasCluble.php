<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CredenciasCluble extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'credencias_clubles';

    protected $fillable = [
        'title',
        'user_id',
        'grant_type',
        'client_id',
        'client_secret',
        'scope',
        'token_type',
        'expires_in',
        'access_token',
        'token_expires_at',
        'refresh_token',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'expires_in'       => 'integer',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
        'deleted_at'       => 'datetime',
    ];

    protected $hidden = [
        'client_secret',
        'access_token',
        'refresh_token',
    ];

    /**
     * Relação com o usuário
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Verifica se o token está expirado
     */
    public function isTokenExpired(): bool
    {
        if (! $this->token_expires_at) {
            return true;
        }

        return $this->token_expires_at->isPast();
    }

    /**
     * Verifica se o token vai expirar em X minutos
     */
    public function expiresInMinutes(int $minutes = 5): bool
    {
        if (! $this->token_expires_at) {
            return true;
        }

        return now()->addMinutes($minutes)->gte($this->token_expires_at);
    }

    /**
     * Atualiza o token e calcula a data de expiração
     */
    public function updateToken(array $tokenData): self
    {
        $this->update([
            'access_token'     => $tokenData['access_token'] ?? $this->access_token,
            'token_type'       => $tokenData['token_type'] ?? $this->token_type,
            'expires_in'       => $tokenData['expires_in'] ?? $this->expires_in,
            'refresh_token'    => $tokenData['refresh_token'] ?? $this->refresh_token,
            'scope'            => $tokenData['scope'] ?? $this->scope,
            'token_expires_at' => isset($tokenData['expires_in'])
                ? now()->addSeconds($tokenData['expires_in'])
                : $this->token_expires_at,
        ]);

        return $this;
    }

    /**
     * Scope para tokens ativos (não expirados)
     */
    public function scopeActive($query)
    {
        return $query->where('token_expires_at', '>', now());
    }

    /**
     * Scope para tokens expirados
     */
    public function scopeExpired($query)
    {
        return $query->where('token_expires_at', '<=', now());
    }

    /**
     * Retorna o header de autorização formatado
     */
    public function getAuthorizationHeader(): ?string
    {
        if (! $this->access_token || $this->isTokenExpired()) {
            return null;
        }

        $type = ucfirst($this->token_type ?? 'Bearer');

        return "{$type} {$this->access_token}";
    }
}
