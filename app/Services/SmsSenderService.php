<?php

namespace App\Services;

use App\Enums\SmsStatusEnum;
use App\Models\SmsLogs;
use App\Models\Tenant;
use Throwable;

class SmsSenderService
{
    public function __construct(private SimpleSmsService $smsService) {}

    /**
     * Envia SMS com controle de cota, log e retorno do provider.
     */
    public function send(string $phone, string $content, string $tenantId, ?int $patientId = null): array
    {
        $tenant = Tenant::find($tenantId);

        if ($tenant && ! $tenant->hasSmsQuota()) {
            SmsLogs::create([
                'tenant_id'     => $tenantId,
                'patient_id'    => $patientId,
                'recipient'     => $phone,
                'message'       => $content,
                'status'        => SmsStatusEnum::Failed,
                'error_message' => 'Cota de SMS esgotada.',
            ]);

            return [
                'sent'       => false,
                'message_id' => null,
                'error'      => 'Cota de SMS esgotada.',
            ];
        }

        $log = SmsLogs::create([
            'tenant_id'  => $tenantId,
            'patient_id' => $patientId,
            'recipient'  => $phone,
            'message'    => $content,
            'status'     => SmsStatusEnum::Pending,
        ]);

        try {
            $result = $this->smsService->send($phone, $content);

            if ($result['sent']) {
                $log->update([
                    'status'  => SmsStatusEnum::Sent,
                    'sent_at' => now(),
                ]);
                $tenant?->decrementSmsQuota();

                return $result;
            }

            $log->update([
                'status'        => SmsStatusEnum::Failed,
                'error_message' => $result['error'],
            ]);

            return $result;
        } catch (Throwable $e) {
            $log->update([
                'status'        => SmsStatusEnum::Failed,
                'error_message' => $e->getMessage(),
            ]);

            return [
                'sent'       => false,
                'message_id' => null,
                'error'      => $e->getMessage(),
            ];
        }
    }
}
