<?php

namespace App\Http\Controllers\Tenant\Form;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormsResponseTenent;
use App\Models\SmsTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FormShowController extends Controller
{
    private function getLogoData(Form $form): ?array
    {
        $logoArquivo = $form->arquivos->first();
        if (! $logoArquivo) {
            return null;
        }

        return [
            'url' => $logoArquivo->url,
            'posicao' => $logoArquivo->pivot->posicao ?? 'centro',
        ];
    }

    public function __invoke(Request $request, Form $form): Response
    {
        $user = $request->user();
        if (! $user->can('forms.view') && $form->user_id !== $user->id && ! $form->is_public) {
            abort(403);
        }
        $form->load(['user:id,name', 'fields' => fn ($q) => $q->orderBy('order')]);
        $host = request()->getHost();
        $currentTenant = str($host)->before('.')->toString();
        $currentTenantResponse = FormsResponseTenent::where('form_id', $form->id)
            ->where('tenant_id', $currentTenant)
            ->whereNotNull('response_id')
            ->pluck('status_paciente', 'response_id')
            ->toArray();
        $currentTenantResponseIds = array_keys($currentTenantResponse);
        $responsesQuery = $form->responses()->with('user:id,name,email');
        if (! empty($currentTenantResponseIds)) {
            $responsesQuery->whereIn('id', $currentTenantResponseIds);
        } else {
            $responsesQuery->whereRaw('1 = 0');
        }
        $responses = $responsesQuery->latest()->paginate(20);
        $stats = [
            'total_responses' => count($currentTenantResponseIds),
            'last_response_at' => $responses->first()?->created_at?->format('d/m/Y H:i'),
        ];

        $fieldStats = [];
        $allResponses = $form->responses()->whereIn('id', $currentTenantResponseIds)->pluck('answers')->toArray();
        foreach ($form->fields as $field) {
            $counts = [];
            foreach ($allResponses as $answerSet) {
                $value = $answerSet[$field->id] ?? null;
                if ($value === null || $value === '') {
                    continue;
                }
                if (is_array($value)) {
                    foreach ($value as $v) {
                        $key = (string) $v;
                        $counts[$key] = ($counts[$key] ?? 0) + 1;
                    }
                } else {
                    $key = (string) $value;
                    $counts[$key] = ($counts[$key] ?? 0) + 1;
                }
            }
            if (! empty($counts)) {
                $fieldStats[$field->id] = [
                    'label' => $field->label,
                    'type' => $field->type,
                    'data' => $counts,
                ];
            }
        }

        return Inertia::render('Tenant/Form/Show', [
            'form' => [
                'id' => $form->id,
                'code' => $form->code,
                'title' => $form->title,
                'slug' => $form->slug,
                'description' => $form->description,
                'status' => $form->status,
                'is_public' => $form->is_public,
                'responses_count' => count($currentTenantResponseIds),
                'created_by' => $form->user->name,
                'primary_color' => $form->primary_color,
                'secondary_color' => $form->secondary_color,
                'lei_id' => $form->lei_id,
                'categoria_id' => $form->categoria_id,
                'settings' => $form->settings,
                'lei' => $form->lei,
                'responses' => count($currentTenantResponseIds),
                'categoria' => $form->categoria,
                'logo' => $this->getLogoData($form),
                'fields' => $form->fields->map(fn ($f) => [
                    'id' => $f->id,
                    'type' => $f->type,
                    'label' => $f->label,
                    'required' => $f->required,
                    'options' => $f->options,
                ]),
            ],
            'stats' => $stats,
            'fieldStats' => $fieldStats,
            'responses' => $responses->through(fn ($r) => [
                'id' => $r->id,
                'respondent' => $r->user?->name ?? null,
                'email' => $r->user?->email ?? null,
                'answers' => $r->answers,
                'ip_address' => $r->ip_address,
                'created_at' => $r->created_at,
                'status_paciente' => $currentTenantResponse[$r->id] ?? false,
            ]),
            'can' => [
                'edit' => $user->can('forms.edit') || $form->user_id === $user->id,
                'delete' => $user->can('forms.delete') || $form->user_id === $user->id,
            ],
            'smsTemplates' => SmsTemplate::query()
                ->where('event', 'patient.created')
                ->orderByDesc('updated_at')
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'message' => $t->message,
                    'event' => $t->event->value,
                    'plan_id' => $t->plan_id,
                    'variables' => $t->variables,
                    'is_active' => $t->is_active,
                    'created_at' => $t->created_at,
                    'updated_at' => $t->updated_at,
                ]),
        ]);
    }
}
