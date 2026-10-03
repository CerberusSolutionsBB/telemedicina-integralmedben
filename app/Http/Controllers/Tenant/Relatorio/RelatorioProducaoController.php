<?php

namespace App\Http\Controllers\Tenant\Relatorio;

use App\Http\Controllers\Controller;
use App\Services\Tenant\ProducaoUsuariosArquivos;
use App\Services\Tenant\ProducaoUsuariosService;
use App\Support\Formatar;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Relatório de produção por usuário (aba Relatórios da tela Relatório).
 */
class RelatorioProducaoController extends Controller
{
    public function __construct(
        private readonly ProducaoUsuariosService $producao,
        private readonly ProducaoUsuariosArquivos $arquivos,
    ) {}

    public function __invoke(Request $request, string $formato): Response
    {
        $filtros = $this->validar($request);
        $dados = $this->producao->dados(tenant('id'), $filtros);
        $nome = 'producao-usuarios-'.now(Formatar::FUSO)->format('Y-m-d');

        return $formato === 'xlsx'
            ? new Response($this->arquivos->xlsx($dados), 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="'.$nome.'.xlsx"',
            ])
            : new Response($this->arquivos->pdf(tenant('id'), $dados), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$nome.'.pdf"',
            ]);
    }

    private function validar(Request $request): array
    {
        $padrao = $this->producao->periodoPadrao();
        $request->mergeIfMissing($padrao);

        return $request->validate([
            'de' => ['required', 'date_format:Y-m-d'],
            'ate' => ['required', 'date_format:Y-m-d', 'after_or_equal:de',
                function (string $attribute, mixed $valor, \Closure $fail) use ($request) {
                    $dias = Carbon::parse($request->input('de'))->diffInDays(Carbon::parse($valor));
                    if ($dias >= ProducaoUsuariosService::MAX_DIAS) {
                        $fail('O período pode ter no máximo '.ProducaoUsuariosService::MAX_DIAS.' dias.');
                    }
                }],
            'usuario' => ['nullable', 'integer'],
            'perfil' => ['nullable', 'string', 'max:255'],
            'plano' => ['nullable', 'string', 'max:50'],
            'origem' => ['nullable', Rule::in(array_keys(ProducaoUsuariosService::ORIGENS))],
        ], [
            'ate.after_or_equal' => 'A data final deve ser igual ou posterior à inicial.',
        ]);
    }
}
