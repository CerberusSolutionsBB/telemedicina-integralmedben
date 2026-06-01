<?php

namespace App\Services\Tenant;

use App\Models\FormsResponseTenent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class FormsResponseTenentService
{
    /**
     * Cria um registro no banco CENTRAL
     */
    public function create(?string $tenantId = null, ?string $formId = null, ?string $responseId = null): void
    {
        $resolvedTenantId = null;

        try {
            $resolvedTenantId = $tenantId ?? tenant('id');

            if (empty($resolvedTenantId)) {
                throw new \RuntimeException('ID do tenant não fornecido e não pode ser resolvido.');
            }

            FormsResponseTenent::create([
                'code' => Str::uuid(),
                'form_id' => $formId,
                'tenant_id' => $resolvedTenantId,
                'response_id' => $responseId,
            ]);

        } catch (\RuntimeException $e) {
            Log::error('[FormsResponseTenentService] Erro de validação', [
                'mensagem' => $e->getMessage(),
                'tenant_id' => $resolvedTenantId,
            ]);
            throw $e;
        } catch (Throwable $e) {
            Log::error('[FormsResponseTenentService] Erro inesperado', [
                'mensagem' => $e->getMessage(),
                'classe' => get_class($e),
                'arquivo' => $e->getFile(),
                'linha' => $e->getLine(),
                'tenant_id' => $resolvedTenantId,
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \RuntimeException(
                'Falha ao registrar resposta no banco central: '.$e->getMessage()
            );
        }
    }
}
