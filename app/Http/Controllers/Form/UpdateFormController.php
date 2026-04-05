<?php
namespace App\Http\Controllers\Form;

use App\Http\Controllers\Controller;
use App\Http\Requests\Form\UpdateFormRequest;
use App\Models\Arquivo;
use App\Models\Form;
use App\Models\FormArquivo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UpdateFormController extends Controller
{
    public function __invoke(UpdateFormRequest $request, Form $form): RedirectResponse
    {
        try {
            DB::beginTransaction();
            $validated = $request->validated();
            $form->update([
                'title'           => $validated['title'],
                'description'     => $validated['description'] ?? null,
                'categoria_id'    => $validated['categoria_id'] ?? null,
                'lei_id'          => $validated['lei_id'] ?? null,
                'status'          => $validated['status'],
                'is_public'       => $validated['is_public'] ?? false,
                'published_at'    => $validated['published_at'] ?? null,
                'primary_color'   => $validated['primary_color'] ?? '#22d3ee',
                'secondary_color' => $validated['secondary_color'] ?? '#06b6d4',
                'expires_at'      => $validated['expires_at'] ?? null,
                'response_limit'  => $validated['response_limit'] ?? null,
                'settings'        => $validated['settings'] ?? null,
            ]);
            $this->syncLogo($form, $request, $validated);
            if (isset($validated['fields'])) {
                $this->syncFields($form, $validated['fields']);
            }
            DB::commit();

            return redirect()
                ->route('forms.index')
                ->with('success', 'Formulário atualizado com sucesso!');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar formulário', [
                'form_id' => $form->id,
                'user_id' => $request->user()->id,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erro ao atualizar formulário: ' . $e->getMessage());
        }
    }
    private function syncLogo(Form $form, $request, array $validated): void
    {
        $logoAtual = $form->logoArquivo;
        if ($request->boolean('remove_logo')) {
            if ($logoAtual) {
                $this->removerLogo($form, $logoAtual);
            }
            return;
        }
        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            if ($logoAtual) {
                $this->removerLogo($form, $logoAtual);
            }
            $posicao = $validated['logo_posicao'] ?? 'centro';
            $this->processarLogo($form, $request->file('logo'), $posicao, $request->user()->id);
            return;
        }
        if ($logoAtual && isset($validated['logo_posicao'])) {
            $logoAtual->update(['posicao' => $validated['logo_posicao']]);
        }
    }
    private function removerLogo(Form $form, FormArquivo $formArquivo): void
    {
        $arquivo = $formArquivo->arquivo;
        if ($arquivo) {
            try {
                if (Storage::disk($arquivo->disk)->exists($arquivo->caminho)) {
                    Storage::disk($arquivo->disk)->delete($arquivo->caminho);
                    Log::info('Logo deletado do storage', ['caminho' => $arquivo->caminho]);
                }
                $arquivo->delete();
            } catch (\Exception $e) {
                Log::error('Erro ao remover arquivo do storage', [
                    'arquivo_id' => $arquivo->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }
        $formArquivo->delete();
        Log::info('Logo removido do formulário', ['form_id' => $form->id]);
    }
    private function processarLogo(Form $form, $file, string $posicao = 'centro', int $userId): void
    {
        $nomeOriginal     = $file->getClientOriginalName();
        $extensao         = $file->getClientOriginalExtension();
        $mimeType         = $file->getMimeType();
        $tamanho          = $file->getSize();
        $nomeArmazenado   = Str::uuid() . '.' . $extensao;
        $caminhoDiretorio = 'forms/logos/' . $form->id;
        $caminhoCompleto  = $caminhoDiretorio . '/' . $nomeArmazenado;
        $disk             = 'public';
        $this->criarDiretorioSeNaoExistir($caminhoDiretorio, $disk);
        $file->storeAs($caminhoDiretorio, $nomeArmazenado, $disk);
        $arquivo = Arquivo::create([
            'nome_original'   => $nomeOriginal,
            'nome_armazenado' => $nomeArmazenado,
            'caminho'         => $caminhoCompleto,
            'extensao'        => $extensao,
            'mime_type'       => $mimeType,
            'tamanho'         => $tamanho,
            'disk'            => $disk,
            'user_id'         => $userId,
        ]);
        FormArquivo::create([
            'arquivo_id' => $arquivo->id,
            'form_id'    => $form->id,
            'tipo'       => FormArquivo::TIPO_LOGO,
            'posicao'    => $posicao,
        ]);
    }
    private function criarDiretorioSeNaoExistir(string $caminho, string $disk): void
    {
        $storage = Storage::disk($disk);
        if (! $storage->exists($caminho)) {
            $storage->makeDirectory($caminho);
            $caminhoCompleto = $storage->path($caminho);
            if (is_dir($caminhoCompleto)) {
                chmod($caminhoCompleto, 0755);
            }
            Log::info("Diretório criado: {$caminho} no disco {$disk}");
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