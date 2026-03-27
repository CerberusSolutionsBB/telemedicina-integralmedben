<?php
namespace App\Http\Controllers\Form;

use App\Http\Controllers\Controller;
use App\Models\Form;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexFormController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $forms = Form::query()
            ->with('user:id,name')
            ->when($user, function ($query) use ($user) {
                if (! $user->hasAnyRole(['Admin', 'Manager'])) {
                    $query->where(function ($q) use ($user) {
                        $q->where('user_id', $user->id)
                            ->orWhere('is_public', true);
                    });
                }
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest('updated_at')
            ->paginate(10)
            ->withQueryString();

        // Mapear formulários com URL pública
        $formsMapped = $forms->through(function ($form) {
            return [
                'id'              => $form->id,
                'code'            => $form->code,
                'title'           => $form->title,
                'slug'            => $form->slug,
                'description'     => $form->description,
                'status'          => $form->status,
                'is_public'       => $form->is_public,
                'responses_count' => $form->responses_count,
                'created_by'      => $form->user->name ?? 'Desconhecido',
                'updated_at'      => $form->updated_at->format('d/m/Y H:i'),
                // URL pública para responder o formulário
                'public_url'      => $form->is_public && $form->status === 'ativo'
                    ? route('forms.public.show', $form->slug)
                    : null,
            ];
        });

        return Inertia::render('Form/Index', [
            'forms'         => $formsMapped,
            'filters'       => $request->only(['status', 'search']),
            'statusOptions' => [
                ['value' => '', 'label' => 'Todos'],
                ['value' => 'rascunho', 'label' => 'Rascunho'],
                ['value' => 'ativo', 'label' => 'Ativo'],
                ['value' => 'pausado', 'label' => 'Pausado'],
                ['value' => 'encerrado', 'label' => 'Encerrado'],
            ],
            'can'           => [
                'create' => $user->can('forms.create'),
                'edit'   => $user->can('forms.edit'),
                'delete' => $user->can('forms.delete'),
                'manage' => $user->hasAnyRole(['Admin', 'Manager']),
            ],
        ]);
    }
}