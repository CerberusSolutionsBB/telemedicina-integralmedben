<?php

namespace App\Http\Controllers\Siprov;

use App\Data\SiprovIntegrationData;
use App\Exceptions\SiprovException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Siprov\CreateSiprovIntegrationRequest;
use App\Models\Siprov;
use App\Services\Siprov\SiprovIntegrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateSiprovIntegrationController extends Controller
{
    public function __invoke(
        CreateSiprovIntegrationRequest $request,
        SiprovIntegrationService $service
    ): RedirectResponse {
        try {

            $data = SiprovIntegrationData::fromRequest($request);

            $result = $service->execute($data);

            Siprov::updateOrCreate(
                [
                    'codigo_integracao' => $data->codigoIntegracao,
                    'cpf_cnpj' => $data->cpfCnpj,
                    'cod_plano' => $data->codPlano(),
                ],
                [
                    'user_id' => auth()->id(),

                    'nome_pessoa' => $data->nomePessoa,
                    'email' => $data->email,
                    'sexo' => $data->sexo,
                    'data_nascimento' => $data->dataNascimento,

                    'cod_loja' => (int) config('siprov.cod_loja'),

                    'dia_vencimento' => $data->diaVencimento,
                    'ativo' => $data->ativo,
                    'situacao' => $data->situacao,

                    'associado' => $result['associado'] ?? [],
                    'beneficio' => $result['beneficio'] ?? [],

                    'status' => Siprov::STATUS_SUCCESS,
                    'error_message' => null,
                    'integrated_at' => now(),
                ]
            );

            return to_route('siprov.index')
                ->with('message', 'Associado e benefício integrados com sucesso na SIPROV.')
                ->with('type', 'success');
        } catch (SiprovException $e) {
            Log::error('Erro de integração SIPROV.', [
                'message' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return back()
                ->withErrors([
                    'siprov' => $e->getMessage(),
                ])
                ->withInput();
        } catch (Throwable $e) {
            Log::error('Erro interno ao processar integração SIPROV.', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'data' => $request->all(),
            ]);

            return back()
                ->withErrors([
                    'siprov' => 'Erro interno ao processar integração SIPROV.',
                ])
                ->withInput();
        }
    }
}
