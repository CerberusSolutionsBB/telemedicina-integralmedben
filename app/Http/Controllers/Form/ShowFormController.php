<?php

namespace App\Http\Controllers\Form;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\SmsTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShowFormController extends Controller
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
        $stats = [
            'total_responses' => $form->responses_count ?? 0,
            'last_response_at' => $form->responses()->latest()->first()?->created_at?->format('d/m/Y H:i'),
        ];
        $responses = $form->responses()
            ->with('user:id,name,email')
            ->latest()
            ->paginate(20);

        return Inertia::render('Form/Show', [
            'form' => [
                'id' => $form->id,
                'code' => $form->code,
                'title' => $form->title,
                'slug' => $form->slug,
                'description' => $form->description,
                'status' => $form->status,
                'is_public' => $form->is_public,
                'responses_count' => $form->responses_count,
                'created_by' => $form->user->name,
                'primary_color' => $form->primary_color,
                'secondary_color' => $form->secondary_color,
                'lei_id' => $form->lei_id,
                'categoria_id' => $form->categoria_id,
                'settings' => $form->settings,
                'lei' => $form->lei,
                'responses' => $form->responses()->count(),
                'categoria' => $form->categoria,
                'configuracao' => $form->configuracao,
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
            'responses' => $responses,
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
