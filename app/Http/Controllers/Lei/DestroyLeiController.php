<?php
namespace App\Http\Controllers\Lei;

use App\Http\Controllers\Controller;
use App\Models\Lei;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DestroyLeiController extends Controller
{
    public function __invoke(Lei $lei): RedirectResponse
    {
        if ($lei->trashed()) {
            return redirect()
                ->back()
                ->with('error', 'Esta lei já foi excluída.');
        }
        DB::beginTransaction();
        try {
            $leiTitle = $lei->title;
            $lei->delete();
            DB::commit();
            Log::info('Lei excluída (soft delete)', [
                'lei_id' => $lei->id,
                'title'  => $leiTitle,
                'user'   => auth()->id(),
            ]);
            return redirect()
                ->route('leis.index')
                ->with('success', 'Lei "' . $leiTitle . '" excluída com sucesso!');
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Erro ao excluir lei', [
                'error'  => $e->getMessage(),
                'lei_id' => $lei->id,
                'user'   => auth()->id(),
            ]);
            return redirect()
                ->back()
                ->with('error', 'Erro ao excluir lei. Tente novamente.');
        }
    }
}