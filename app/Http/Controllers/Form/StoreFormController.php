<?php
namespace App\Http\Controllers\Form;

use App\Http\Controllers\Controller;
use App\Http\Requests\Form\StoreFormRequest;
use App\Models\Form;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoreFormController extends Controller
{
    public function __invoke(StoreFormRequest $request)
    {
        $validated = $request->validated();
        try {
            DB::beginTransaction();
            $form = Form::create([
                'user_id'         => auth()->id(),
                'categoria_id'    => $validated['categoria_id'] ?? null,
                'lei_id'          => $validated['lei_id'] ?? null,
                'title'           => $validated['title'],
                'slug'            => $this->generateUniqueSlug($validated['title']),
                'description'     => $validated['description'] ?? null,
                'status'          => $validated['status'],
                'is_public'       => $validated['is_public'] ?? false,
                'published_at'    => $validated['published_at'] ?? null,
                'expires_at'      => $validated['expires_at'] ?? null,
                'response_limit'  => $validated['response_limit'] ?? null,
                'settings'        => $validated['settings'] ?? [],
                'responses_count' => 0,
            ]);
            foreach ($validated['fields'] as $index => $fieldData) {
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
            DB::commit();
            return redirect()
                ->route('forms.index')
                ->with('success', 'Formulário criado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erro ao criar formulário: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erro ao criar formulário. Tente novamente.');
        }
    }
    private function generateUniqueSlug(string $title): string
    {
        $baseSlug = Str::slug($title);
        $slug     = $baseSlug . '-' . Str::random(6);
        $counter  = 1;
        while (Form::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . Str::random(6) . '-' . $counter;
            $counter++;
        }
        return $slug;
    }
}