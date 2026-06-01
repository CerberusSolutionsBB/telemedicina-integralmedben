<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TenantAuthContextService extends BaseAuthContextService
{
    public function current(): array
    {
        $user = Auth::user();

        Log::debug('[TenantAuthContext] Iniciando verificação de contexto', [
            'has_auth_user' => (bool) $user,
            'tenancy_initialized' => tenancy()->initialized,
        ]);

        if (! $user) {
            Log::info('[TenantAuthContext] Usuário não autenticado, retornando guest context');

            return $this->guestContext('tenant');
        }

        Log::debug('[TenantAuthContext] Usuário autenticado encontrado', [
            'user_id' => $user->id,
            'user_email' => $user->email,
        ]);

        if (! $this->isValidTenantUser($user->id, $user->email)) {
            Log::warning('[TenantAuthContext] Usuário inválido para o tenant atual', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'tenant' => tenancy()->initialized ? tenant() : null,
                'tenant_id' => tenancy()->initialized ? tenant('id') : null,
            ]);

            // Remove o dd() — agora você loga e redireciona/logout
            $this->logoutInvalidSession();

            return $this->guestContext('tenant');
        }

        Log::info('[TenantAuthContext] Usuário validado com sucesso no tenant', [
            'user_id' => $user->id,
            'tenant_id' => tenant('id'),
        ]);

        return [
            'user' => $this->userContext($user),
            'can' => $this->permissions($user),
            'check' => true,
            'type' => 'tenant',
            'tenant' => [
                'id' => tenant('id'),
            ],
        ];
    }

    private function isValidTenantUser(int $userId, string $email): bool
    {
        Log::debug('[TenantAuthContext] Validando usuário no tenant', [
            'user_id' => $userId,
            'email' => $email,
            'tenancy_initialized' => tenancy()->initialized,
        ]);

        if (! tenancy()->initialized) {
            Log::warning('[TenantAuthContext] Tenancy não inicializado ao validar usuário');

            return false;
        }

        $exists = DB::table('users')
            ->where('id', $userId)
            ->where('email', $email)
            ->exists();

        Log::debug('[TenantAuthContext] Resultado da validação no banco', [
            'user_id' => $userId,
            'exists' => $exists,
            'connection' => DB::connection()->getName(),
            'database' => DB::connection()->getDatabaseName(),
        ]);

        return $exists;
    }

    protected function logoutInvalidSession(): void
    {
        Log::info('[TenantAuthContext] Executando logout de sessão inválida', [
            'user_id' => Auth::id(),
        ]);

        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }
}
