<?php

namespace App\Http\Services\Sms;

use App\Models\Patient;
use App\Models\Tenant;
use App\Services\SmsSenderService;
use App\Support\Formatar;
use App\Support\Planos;

/**
 * SMS de confirmação do cadastro manual do beneficiário (Novo beneficiário).
 * Envia com o controle de cota e grava o log no histórico do beneficiário.
 */
class PatientRegistrationSmsService
{
    public function __construct(private SmsSenderService $smsSender) {}

    /**
     * @return array{sent: bool, message_id: ?string, error: ?string}
     */
    public function execute(Patient $patient, string $tenantId, ?string $codPlano): array
    {
        if (blank($patient->numero)) {
            return [
                'sent' => false,
                'message_id' => null,
                'error' => 'O beneficiário não tem celular informado.',
            ];
        }

        return $this->smsSender->send(
            (string) $patient->numero,
            $this->mensagem($patient, $tenantId, $codPlano),
            $tenantId,
            $patient->id,
        );
    }

    private function mensagem(Patient $patient, string $tenantId, ?string $codPlano): string
    {
        $empresa = Tenant::find($tenantId)?->name ?: config('app.name');
        $plano = collect(Planos::options())->pluck('label', 'value')[$codPlano] ?? null;
        $agora = now(Formatar::FUSO);

        $planoTexto = $plano ? " no plano {$plano}" : '';

        return "{$empresa}, Olá {$patient->nome}! Seu cadastro{$planoTexto} foi realizado com sucesso "
            ."no dia {$agora->format('d/m/Y')} às {$agora->format('H:i')}.";
    }
}
