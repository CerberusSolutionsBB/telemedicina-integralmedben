<?php
namespace App\Http\Controllers\Lei;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lei\UpdateLeiRequest;
use App\Models\Lei;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class UpdateLeiController extends Controller
{
    public function __invoke(UpdateLeiRequest $request, Lei $lei): RedirectResponse
    {
        if ($lei->trashed()) {
            return redirect()
                ->back()
                ->with('error', 'Não é possível editar uma lei excluída. Restaure-a primeiro.');
        }
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $lei->update($data);
            DB::commit();

            return redirect()
                ->route('leis.index', $lei)
                ->with('success', 'Lei "' . $lei->title . '" atualizada com sucesso!');
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar lei', [
                'error'  => $e->getMessage(),
                'lei_id' => $lei->id,
                'data'   => $request->validated(),
                'user'   => auth()->id(),
            ]);
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erro ao atualizar lei. Tente novamente.');
        }
    }
}