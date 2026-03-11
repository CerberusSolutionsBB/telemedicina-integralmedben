<?php

namespace App\Http\Services\Sms;

use App\Models\SmsGlobalBalance;
use App\Models\SmsGlobalBalanceLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AddGlobalSmsBalanceService
{
    public function execute(int $amount, ?string $notes = null): SmsGlobalBalance
    {
        return DB::transaction(function () use ($amount, $notes) {
            $balance = SmsGlobalBalance::instance();
            $balance->increment('balance', $amount);
            $balance->refresh();

            SmsGlobalBalanceLog::create([
                'amount'        => $amount,
                'type'          => 'recharge',
                'tenant_id'     => null,
                'added_by'      => Auth::id(),
                'notes'         => $notes,
                'balance_after' => $balance->balance,
            ]);

            return $balance;
        });
    }
}
