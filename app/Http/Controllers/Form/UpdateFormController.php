<?php
namespace App\Http\Controllers\Form;

use App\Http\Controllers\Controller;
use App\Http\Requests\Form\UpdateFormRequest;
use App\Models\Form;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateFormController extends Controller
{
    public function __invoke(UpdateFormRequest $request, Form $form): RedirectResponse
    {
        if ($form->responses_count > 0 || $form->responses()->exists()) {
            Log::warning('Tentativa de editar formulário com respostas', [
                'form_id'         => $form->id,
                'user_id'         => $request->user()->id,
                'responses_count' => $form->responses_count,
            ]);
            return redirect()
                ->back()
                ->with('error', 'Este formulário não pode ser editado porque já possui respostas. Crie uma nova versão ou duplique o formulário.');
        }
        try {
            DB::beginTransaction();
            $validated = $request->validated();
            $form->update([
                'title'          => $validated['title'],
                'description'    => $validated['description'] ?? null,
                'categoria_id'   => $validated['categoria_id'] ?? null,
                'lei_id'         => $validated['lei_id'] ?? null,
                'status'         => $validated['status'],
                'is_public'      => $validated['is_public'] ?? false,
                'published_at'   => $validated['published_at'] ?? null,
                'expires_at'     => $validated['expires_at'] ?? null,
                'response_limit' => $validated['response_limit'] ?? null,
                'settings'       => $validated['settings'] ?? null,
            ]);
            $this->syncFields($form, $validated['fields']);
            DB::commit();
            Log::info('Formulário atualizado', [
                'form_id' => $form->id,
                'user_id' => $request->user()->id,
            ]);
            return redirect()
                ->route('forms.index', $form)
                ->with('success', 'Formulário atualizado com sucesso!');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar formulário', [
                'form_id' => $form->id,
                'user_id' => $request->user()->id,
                'error'   => $e->getMessage(),
            ]);
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erro ao atualizar formulário. Tente novamente.');
        }
    }
    private function syncFields(Form $form, array $fields): void
    {
        $form->fields()->delete();
        foreach ($fields as $index => $fieldData) {
            $form->fields()->create([
                'type'        => $fieldData['type'],
                'label'       => $fieldData['label'],
                'placeholder' => $fieldData['placeholder'] ?? null,
                'required'    => $fieldData['required'] ?? false,
                'options'     => in_array($fieldData['type'], ['select', 'checkbox', 'radio'])
                    ? ($fieldData['options'] ?? [])
                    : [],
                'help_text'   => $fieldData['help_text'] ?? null,
                'order'       => $fieldData['order'] ?? $index,
            ]);
        }
    }
}