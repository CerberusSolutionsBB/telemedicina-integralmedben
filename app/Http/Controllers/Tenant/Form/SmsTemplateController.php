<?php

namespace App\Http\Controllers\Tenant\Form;

use App\Http\Controllers\Controller;
use App\Models\SmsTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class SmsTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $templates = SmsTemplate::query()
            ->where('event', $request->input('event'))
            ->when($request->input('plan_id'), fn ($q) => $q->where('plan_id', $request->input('plan_id')))
            ->orderByDesc('updated_at')
            ->get();

        return response()->json($templates);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'message' => 'required|string',
            'event' => 'required|string|in:patient.created,siprov.integrated',
            'plan_id' => 'nullable|string',
            'form_ids' => 'nullable|array',
            'variables' => 'nullable|array',
            'is_active' => 'boolean',
            'tenant_ids' => 'nullable|array',
            'tenant_ids.*' => 'exists:tenants,id',
        ]);

        try {
            $template = SmsTemplate::create([
                'name' => $validated['name'],
                'message' => $validated['message'],
                'channel' => 'sms',
                'event' => $validated['event'],
                'plan_id' => $validated['plan_id'] ?? null,
                'form_ids' => $validated['form_ids'] ?? [],
                'recipient_variable' => 'tel',
                'variables' => $validated['variables'] ?? [],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            if (!empty($validated['tenant_ids'])) {
                $template->tenants()->sync($validated['tenant_ids']);
            }

            return response()->json([
                'message' => 'Template criado com sucesso.',
                'template' => $template,
            ]);
        } catch (Throwable $e) {
            Log::error('SmsTemplate | Erro ao criar template', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erro ao criar template: '.$e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, SmsTemplate $smsTemplate): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'message' => 'required|string',
            'plan_id' => 'nullable|string',
            'form_ids' => 'nullable|array',
            'variables' => 'nullable|array',
            'is_active' => 'boolean',
            'tenant_ids' => 'nullable|array',
            'tenant_ids.*' => 'exists:tenants,id',
        ]);

        try {
            $smsTemplate->update([
                'name' => $validated['name'],
                'message' => $validated['message'],
                'plan_id' => $validated['plan_id'] ?? $smsTemplate->plan_id,
                'form_ids' => $validated['form_ids'] ?? $smsTemplate->form_ids,
                'variables' => $validated['variables'] ?? $smsTemplate->variables,
                'is_active' => $validated['is_active'] ?? $smsTemplate->is_active,
            ]);

            if (array_key_exists('tenant_ids', $validated)) {
                $smsTemplate->tenants()->sync($validated['tenant_ids'] ?? []);
            }

            return response()->json([
                'message' => 'Template atualizado com sucesso.',
                'template' => $smsTemplate,
            ]);
        } catch (Throwable $e) {
            Log::error('SmsTemplate | Erro ao atualizar template', [
                'template_id' => $smsTemplate->id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erro ao atualizar template: '.$e->getMessage(),
            ], 500);
        }
    }

    public function destroy(SmsTemplate $smsTemplate): JsonResponse
    {
        try {
            $smsTemplate->delete();

            return response()->json([
                'message' => 'Template removido com sucesso.',
            ]);
        } catch (Throwable $e) {
            Log::error('SmsTemplate | Erro ao remover template', [
                'template_id' => $smsTemplate->id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erro ao remover template: '.$e->getMessage(),
            ], 500);
        }
    }
}
