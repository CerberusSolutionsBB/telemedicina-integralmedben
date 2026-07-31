<?php

namespace App\Http\Services\Sms;

use App\Enums\SmsStatusEnum;
use App\Models\Patient;
use App\Models\SmsLogs;
use App\Models\Tenant;
use App\Services\SimpleSmsService;

class ResendSmsService
{
    public function __construct(private SimpleSmsService $smsService) {}

    public function execute(Patient $patient, string $tenantId): array
    {
        $tenant = Tenant::findOrFail($tenantId);

        if (! $tenant->hasSmsQuota()) {
            return ['success' => false, 'message' => 'Cota de SMS esgotada para este tenant.'];
        }

        $pendingLogs = SmsLogs::where('tenant_id', $tenantId)
            ->where('patient_id', $patient->id)
            ->whereIn('status', [SmsStatusEnum::Pending->value, SmsStatusEnum::Failed->value])
            ->get();

        if ($pendingLogs->isEmpty()) {
            return ['success' => false, 'message' => 'Nenhum SMS pendente encontrado para este paciente.'];
        }

        $sent = 0;

        foreach ($pendingLogs as $log) {
            if (! $tenant->hasSmsQuota()) {
                break;
            }

            if ($this->resendLog($log, $tenant)) {
                $sent++;
            }
        }

        return [
            'success' => $sent > 0,
            'message' => $sent > 0
                ? "{$sent} SMS reenviado(s) com sucesso."
                : 'Falha ao reenviar SMS.',
        ];
    }

    public function executeOne(Patient $patient, SmsLogs $log, string $tenantId): array
    {
        if ((int) $log->patient_id !== $patient->id || $log->tenant_id !== $tenantId) {
            return ['success' => false, 'message' => 'SMS não pertence a este paciente.'];
        }

        $tenant = Tenant::findOrFail($tenantId);

        if (! $tenant->hasSmsQuota()) {
            return ['success' => false, 'message' => 'Cota de SMS esgotada para este tenant.'];
        }

        $sent = $this->resendLog($log, $tenant);

        return [
            'success' => $sent,
            'message' => $sent ? 'SMS reenviado com sucesso.' : 'Falha ao reenviar SMS.',
        ];
    }

    public function executeOneAdmin(SmsLogs $log): array
    {
        if (! $log->tenant_id) {
            return ['success' => false, 'message' => 'SMS sem credenciado associado.'];
        }

        $tenant = Tenant::findOrFail($log->tenant_id);

        if (! $tenant->hasSmsQuota()) {
            return ['success' => false, 'message' => 'Cota de SMS esgotada para este credenciado.'];
        }

        $sent = $this->resendLog($log, $tenant);

        return [
            'success' => $sent,
            'message' => $sent ? 'SMS reenviado com sucesso.' : 'Falha ao reenviar SMS.',
        ];
    }

    private function resendLog(SmsLogs $log, Tenant $tenant): bool
    {
        try {
            $result = $this->smsService->send($log->recipient, $log->message);

            if (! $result['sent']) {
                $log->update([
                    'status' => SmsStatusEnum::Failed,
                    'error_message' => $result['error'] ?? 'Falha ao enviar SMS.',
                ]);

                return false;
            }

            $log->update([
                'status' => SmsStatusEnum::Sent,
                'sent_at' => now(),
                'error_message' => null,
            ]);

            $tenant->decrementSmsQuota();

            return true;
        } catch (\Throwable $e) {
            $log->update([
                'status' => SmsStatusEnum::Failed,
                'error_message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
