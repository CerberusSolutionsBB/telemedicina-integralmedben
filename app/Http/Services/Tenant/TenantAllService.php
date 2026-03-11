<?php

namespace App\Http\Services\Tenant;

use App\Models\Question;
use App\Models\Tenant;
use Inertia\Inertia;

class TenantAllService
{
    public function execute()
    {
        $tenants = Tenant::query()
            ->with([
                'domain' => fn ($query) =>
                    $query->select('id', 'tenant_id', 'domain'),
                'questions'
            ])
            ->withCount([
                'centralPatients',
                'smsLogs as sms_sent_count'    => fn ($q) => $q->where('status', 'sent'),
                'smsLogs as sms_pending_count' => fn ($q) => $q->where('status', 'pending'),
                'smsLogs as sms_failed_count'  => fn ($q) => $q->where('status', 'failed'),
            ])
            ->latest()
            ->paginate();

        $questions = Question::where('is_active', true)->get();

        return Inertia::render('Credenciados/Index', [
            'tenants' => $tenants,
            'questions' => $questions
        ]);
    }
}
