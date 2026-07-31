<?php

namespace App\Http\Controllers;

use App\Http\Services\Sms\AddGlobalSmsBalanceService;
use App\Http\Services\Sms\ResendSmsService;
use App\Models\Patient;
use App\Models\SmsGlobalBalance;
use App\Models\SmsGlobalBalanceLog;
use App\Models\SmsLogs;
use App\Models\SmsQuotaLog;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SmsLogsController extends Controller
{
    public function __construct(
        private AddGlobalSmsBalanceService $addGlobalBalanceService,
        private ResendSmsService $resendSmsService,
    ) {}

    public function index(Request $request)
    {
        $logs = SmsLogs::query()
            ->with('tenant.details')
            ->when($request->search, function ($q, $search) use ($request) {
                $patientMatches = $this->findPatientIdsByName($search, $request->tenant_id);

                $q->where(function ($q) use ($search, $patientMatches) {
                    $q->where('recipient', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%");

                    foreach ($patientMatches as $tenantId => $patientIds) {
                        $q->orWhere(function ($q) use ($tenantId, $patientIds) {
                            $q->where('tenant_id', $tenantId)
                                ->whereIn('patient_id', $patientIds);
                        });
                    }
                });
            })
            ->when($request->tenant_id, fn ($q) => $q->where('tenant_id', $request->tenant_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $this->attachTenantAndPatientInfo($logs->getCollection());

        $tenants = Tenant::with('details')->orderBy('id')->get()->map(fn ($t) => [
            'id' => $t->id,
            'name' => $t->details->first()?->descricao ?? $t->name,
        ]);

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
            'logs' => $logs,
            'quotaLogs' => $quotaLogs,
            'tenants' => $tenants,
            'filters' => $request->only('tenant_id', 'status', 'search'),
            'globalBalance' => $globalBalance->balance,
            'globalLogs' => $globalLogs,
        ]);
    }

    public function addGlobalBalance(Request $request)
    {
        $request->validate([
            'amount' => 'required|integer|min:1|max:1000000',
            'notes' => 'nullable|string|max:255',
        ]);

        $this->addGlobalBalanceService->execute($request->amount, $request->notes);

        return back()->with('success', "{$request->amount} SMS adicionados ao saldo global.");
    }

    public function resendSmsLog(SmsLogs $smsLog)
    {
        $result = $this->resendSmsService->executeOneAdmin($smsLog);

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['message']
        );
    }

    private function findPatientIdsByName(string $search, ?string $onlyTenantId = null): array
    {
        $tenants = Tenant::query()
            ->when($onlyTenantId, fn ($q) => $q->where('id', $onlyTenantId))
            ->get();

        $matches = [];

        foreach ($tenants as $tenant) {
            $tenant->run(function () use ($tenant, $search, &$matches) {
                $ids = Patient::where('nome', 'like', "%{$search}%")->pluck('id');

                if ($ids->isNotEmpty()) {
                    $matches[$tenant->id] = $ids;
                }
            });
        }

        return $matches;
    }

    private function attachTenantAndPatientInfo($logs): void
    {
        $patientNamesByTenant = [];

        foreach ($logs->groupBy('tenant_id') as $tenantId => $tenantLogs) {
            $tenant = $tenantLogs->first()->tenant;

            if (! $tenant) {
                continue;
            }

            $patientIds = $tenantLogs->pluck('patient_id')->filter()->unique()->values();

            if ($patientIds->isEmpty()) {
                continue;
            }

            $tenant->run(function () use ($patientIds, $tenantId, &$patientNamesByTenant) {
                $patientNamesByTenant[$tenantId] = Patient::whereIn('id', $patientIds)->pluck('nome', 'id');
            });
        }

        $logs->each(function ($log) use ($patientNamesByTenant) {
            $log->tenant_descricao = $log->tenant?->details->first()?->descricao;
            $log->patient_nome = $patientNamesByTenant[$log->tenant_id][$log->patient_id] ?? null;
        });
    }
}
