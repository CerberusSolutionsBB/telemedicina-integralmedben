<?php

namespace App\Http\Controllers\Form;

use App\Http\Controllers\Controller;
use App\Models\Form;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Response;

class DestroyFormController extends Controller
{
    public function __invoke(Request $request, Form $form): RedirectResponse|Response
    {
        Gate::authorize('forms.delete');

        // Validação prévia
        if ($form->responses()->count() > 0) {
            return back()->with('error', 'Este formulário possui respostas e não pode ser excluído.');
        }

        try {
            DB::transaction(function () use ($form) {
                $form->fields()->delete();
                $form->delete();
            });

            Log::info('Formulário excluído', [
                'form_id' => $form->id,
                'user_id' => auth()->id(),
                'deleted_at' => now(),
            ]);

            return redirect()
                ->route('forms.index')
                ->with('success', 'Formulário excluído com sucesso!');

        } catch (\Exception $e) {
            Log::error('Erro ao excluir formulário', [
                'form_id' => $form->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Erro ao excluir formulário. Tente novamente.');
        }
    }
}
