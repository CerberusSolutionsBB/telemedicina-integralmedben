<?php
namespace App\Http\Controllers\Form;

use App\Http\Controllers\Controller;
use App\Models\Form;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class DestroyFormController extends Controller
{
    public function __invoke(Request $request, Form $form): RedirectResponse
    {
        Gate::authorize('forms.delete');
        try {
            if ($form->responses()->count() > 0) {
                return redirect()
                    ->back()
                    ->with('warning', 'Este formulário possui respostas e não pode ser excluído. Arquive-o ou exporte os dados primeiro.');
            }
            $form->fields()->delete();
            $form->delete();
            return redirect()
                ->route('forms.index')
                ->with('success', 'Formulário excluído com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao excluir formulário', [
                'form_id' => $form->id,
                'user_id' => auth()->id(),
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return redirect()
                ->back()
                ->with('error', 'Erro ao excluir formulário. Tente novamente.');
        }
    }
}