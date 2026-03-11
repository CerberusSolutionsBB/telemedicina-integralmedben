<?php

namespace App\Http\Services\Tenant;

use App\Models\SmsGlobalBalance;
use App\Models\SmsGlobalBalanceLog;
use App\Models\SmsQuotaLog;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AddSmsQuotaService
{
    public function execute(Tenant $tenant, int $amount, ?string $notes = null): void
    {
        DB::transaction(function () use ($tenant, $amount, $notes) {
            $global = SmsGlobalBalance::instance();

            if (!$global->hasBalance($amount)) {
                throw new \RuntimeException(
                    "Saldo global insuficiente. Disponível: {$global->balance} SMS."
                );
            }

            $global->decrement('balance', $amount);
            $global->refresh();

            SmsGlobalBalanceLog::create([
                'amount'        => -$amount,
                'type'          => 'distribute',
                'tenant_id'     => $tenant->id,
                'added_by'      => Auth::id(),
                'notes'         => $notes,
                'balance_after' => $global->balance,
            ]);

            $tenant->increment('sms_quota', $amount);

            SmsQuotaLog::create([
                'tenant_id' => $tenant->id,
                'amount'    => $amount,
                'added_by'  => Auth::id(),
                'notes'     => $notes,
            ]);
        });
    }
}
