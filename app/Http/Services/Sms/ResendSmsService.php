<?php

namespace App\Http\Services\Sms;

use App\Enums\SmsStatusEnum;
use App\Models\Patient;
use App\Models\SmsLogs;
use App\Models\Tenant;

class ResendSmsService
{
    public function __construct(private SmsService $smsService) {}

    public function execute(Patient $patient, string $tenantId): array
    {
        $tenant = Tenant::findOrFail($tenantId);

        if (!$tenant->hasSmsQuota()) {
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
            if (!$tenant->hasSmsQuota()) {
                break;
            }

            try {
                $this->smsService->send($log->recipient, $log->message);

                $log->update([
                    'status'        => SmsStatusEnum::Sent,
                    'sent_at'       => now(),
                    'error_message' => null,
                ]);

                $tenant->decrementSmsQuota();
                $sent++;
            } catch (\Throwable $e) {
                $log->update([
                    'status'        => SmsStatusEnum::Failed,
                    'error_message' => $e->getMessage(),
                ]);
            }
        }

        return [
            'success' => $sent > 0,
            'message' => $sent > 0
                ? "{$sent} SMS reenviado(s) com sucesso."
                : 'Falha ao reenviar SMS.',
        ];
    }
}
