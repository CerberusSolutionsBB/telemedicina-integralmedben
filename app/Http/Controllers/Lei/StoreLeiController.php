<?php
namespace App\Http\Controllers\Lei;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lei\StoreLeiRequest;
use App\Models\Lei;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class StoreLeiController extends Controller
{
    public function __invoke(StoreLeiRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $request->merge(['user_id' => auth()->id()]);
            $lei = Lei::create($request->validated());
            DB::commit();
            return redirect()
                ->route('leis.index')
                ->with('success', 'Lei "' . $lei->title . '" criada com sucesso!');
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Erro ao criar lei', [
                'error' => $e->getMessage(),
                'data'  => $request->validated(),
                'user'  => auth()->id(),
            ]);
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erro ao criar lei. Tente novamente.');
        }
    }
}