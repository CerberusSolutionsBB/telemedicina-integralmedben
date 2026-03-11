<?php

namespace App\Http\Controllers;

use App\Http\Services\Sms\AddGlobalSmsBalanceService;
use App\Models\SmsGlobalBalance;
use App\Models\SmsGlobalBalanceLog;
use App\Models\SmsLogs;
use App\Models\SmsQuotaLog;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SmsLogsController extends Controller
{
    public function __construct(private AddGlobalSmsBalanceService $addGlobalBalanceService) {}

    public function index(Request $request)
    {
        $logs = SmsLogs::query()
            ->when($request->tenant_id, fn ($q) => $q->where('tenant_id', $request->tenant_id))
            ->when($request->status,    fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $tenants = Tenant::orderBy('id')->get()->map(fn ($t) => ['id' => $t->id, 'name' => $t->name]);

        $quotaLogs = SmsQuotaLog::query()
            ->when($request->tenant_id, fn ($q) => $q->where('tenant_id', $request->tenant_id))
            ->with('addedBy:id,name')
            ->latest()
            ->limit(50)
            ->get();

        $globalBalance = SmsGlobalBalance::instance();

        $globalLogs = SmsGlobalBalanceLog::with('addedBy:id,name')
            ->latest()
            ->limit(30)
            ->get();

        return Inertia::render('SmsLogs/Index', [
            'logs'          => $logs,
            'quotaLogs'     => $quotaLogs,
            'tenants'       => $tenants,
            'filters'       => $request->only('tenant_id', 'status'),
            'globalBalance' => $globalBalance->balance,
            'globalLogs'    => $globalLogs,
        ]);
    }

    public function addGlobalBalance(Request $request)
    {
        $request->validate([
            'amount' => 'required|integer|min:1|max:1000000',
            'notes'  => 'nullable|string|max:255',
        ]);

        $this->addGlobalBalanceService->execute($request->amount, $request->notes);

        return back()->with('success', "{$request->amount} SMS adicionados ao saldo global.");
    }
}
