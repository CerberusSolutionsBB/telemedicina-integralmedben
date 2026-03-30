<?php
namespace App\Http\Controllers\Form;

use App\Http\Controllers\Controller;
use App\Models\Form;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShowFormController extends Controller
{
    public function __invoke(Request $request, Form $form): Response
    {
        $user = $request->user();
        if (! $user->can('forms.view') && $form->user_id !== $user->id && ! $form->is_public) {
            abort(403);
        }
        $form->load(['user:id,name', 'fields' => fn($q) => $q->orderBy('order')]);
        $stats = [
            'total_responses'  => $form->responses_count ?? 0,
            'last_response_at' => $form->responses()->latest()->first()?->created_at?->format('d/m/Y H:i'),
        ];
        $responses = $form->responses()
            ->with('user:id,name,email')
            ->latest()
            ->paginate(20);
        return Inertia::render('Form/Show', [
            'form'      => [
                'id'              => $form->id,
                'code'            => $form->code,
                'title'           => $form->title,
                'slug'            => $form->slug,
                'description'     => $form->description,
                'status'          => $form->status,
                'is_public'       => $form->is_public,
                'responses_count' => $form->responses_count,
                'created_by'      => $form->user->name,
                'fields'          => $form->fields->map(fn($f) => [
                    'id'       => $f->id,
                    'type'     => $f->type,
                    'label'    => $f->label,
                    'required' => $f->required,
                    'options'  => $f->options,
                ]),
            ],
            'stats'     => $stats,
            'responses' => $responses,
            'can'       => [
                'edit'   => $user->can('forms.edit') || $form->user_id === $user->id,
                'delete' => $user->can('forms.delete') || $form->user_id === $user->id,
            ],
        ]);
    }
}