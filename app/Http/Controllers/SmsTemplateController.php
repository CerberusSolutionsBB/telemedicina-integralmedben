<?php

namespace App\Http\Controllers;

use App\Enums\SmsTemplateEventEnum;
use App\Http\Services\SmsTemplate\SmsTemplateService;
use App\Models\Question;
use App\Models\SmsTemplate;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SmsTemplateController extends Controller
{
    public function __construct(private SmsTemplateService $service) {}

    public function index()
    {
        $fixed = collect([
            ['label' => 'Nome do Credenciado', 'key' => 'tenant'],
            ['label' => 'Nome Completo',  'key' => 'nome_completo'],
        ]);

        $roleVariables = Question::whereNotNull('role')->get()->map(fn ($q) => [
            'label' => $q->title,
            'key' => $q->role->value,
        ]);

        $variables = $fixed->merge($roleVariables)->values();

        // Opções de plano vindas da pergunta com role=plan
        $planQuestion = Question::where('role', 'plan')->first();
        $planOptions = $planQuestion?->options ?? [];

        return Inertia::render('SmsTemplate/Index', [
            'templates' => $this->service->getAll(),
            'tenants' => Tenant::orderBy('id')->get(['id', 'data']),
            'events' => collect(SmsTemplateEventEnum::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ]),
            'variables' => $variables,
            'planOptions' => $planOptions,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'message' => 'required|string',
            'channel' => 'required|in:sms',
            'event' => 'required|string',
            'plan_id' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $this->service->create($validated);

        return redirect()->back()->with('success', 'Template criado com sucesso.');
    }

    public function update(Request $request, SmsTemplate $smsTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'message' => 'required|string',
            'channel' => 'required|in:sms',
            'event' => 'required|string',
            'plan_id' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $this->service->update($smsTemplate, $validated);

        return redirect()->back()->with('success', 'Template atualizado com sucesso.');
    }

    public function destroy(SmsTemplate $smsTemplate)
    {
        $this->service->delete($smsTemplate);

        return redirect()->back()->with('success', 'Template excluído com sucesso.');
    }

    public function assignTenants(Request $request, SmsTemplate $smsTemplate)
    {
        $validated = $request->validate([
            'tenants' => 'array',
            'tenants.*' => 'exists:tenants,id',
        ]);

        $this->service->assignTenants($smsTemplate, $validated['tenants'] ?? []);

        return redirect()->back()->with('success', 'Tenants vinculados com sucesso.');
    }
}
