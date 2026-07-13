<?php
namespace App\Http\Controllers\Siprov;

use App\Data\SiprovIntegrationData;
use App\Exceptions\SiprovException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Siprov\CreateSiprovIntegrationRequest;
use App\Models\Siprov;
use App\Services\SimpleSmsService;
use App\Services\Siprov\SiprovIntegrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateSiprovIntegrationController extends Controller
{
    public function __construct(
        private readonly SimpleSmsService $simpleSmsService,
    ) {}

    public function __invoke(
        CreateSiprovIntegrationRequest $request,
        SiprovIntegrationService $service
    ): RedirectResponse {
        try {

            $data = SiprovIntegrationData::fromRequest($request);

            $result = $service->execute($data);

            $siprov = Siprov::withTrashed()
                ->where('codigo_integracao', $data->codigoIntegracao)
                ->where('cpf_cnpj', $data->cpfCnpj)
                ->where('cod_plano', $data->codPlano())
                ->first();

            $attributes = [
                'user_id'         => auth()->id(),
                'nome_pessoa'     => $data->nomePessoa,
                'email'           => $data->email,
                'sexo'            => $data->sexo,
                'data_nascimento' => $data->dataNascimento,
                'cod_loja'        => (int) config('siprov.cod_loja'),
                'dia_vencimento'  => $data->diaVencimento,
                'ativo'           => $data->ativo,
                'situacao'        => $data->situacao,
                'associado'       => $result['associado'] ?? [],
                'beneficio'       => $result['beneficio'] ?? [],
                'status'          => Siprov::STATUS_SUCCESS,
                'error_message'   => null,
                'integrated_at'   => now(),
            ];

            if ($siprov) {
                $siprov->update($attributes);
            } else {
                $siprov = Siprov::create(array_merge([
                    'codigo_integracao' => $data->codigoIntegracao,
                    'cpf_cnpj'          => $data->cpfCnpj,
                    'cod_plano'         => (string) $data->codPlano(),
                ], $attributes));
            }

            // SiprovIntegrated::dispatch(
            //     siprovId: $siprov->id ?? 0,
            //     tenantId: tenant('id'),
            //     nome: $data->nomePessoa,
            //     cpf: $data->cpfCnpj,
            //     email: $data->email,
            //     sexo: $data->sexo,
            //     dataNascimento: $data->dataNascimento,
            //     codPlano: $data->codPlano(),
            //     telefone: $data->telefones[0]['numero'] ?? null,
            // );

            $empresa      = config('app.name', 'Telemedicina');
            $cliente      = $data->nomePessoa;
            $plano        = $data->plano;
            $dataCadastro = now()->format('d/m/Y');
            $hora         = now()->format('H:i');
            $conteudo     = "$empresa, Olá $cliente! Seu cadastro no plano $plano foi realizado com sucesso no dia $dataCadastro às $hora.";

            foreach ($data->telefones as $telefone) {
                $numero = $telefone['numero'] ?? null;
                if ($this->isValidPhone($numero)) {
                    try {
                        $this->simpleSmsService->send($numero, $conteudo);
                    } catch (Throwable $e) {
                        Log::warning('SIPROV | Erro ao enviar SMS de confirmação', [
                            'cellphone' => $numero,
                            'message'   => $e->getMessage(),
                        ]);
                    }
                }
            }

            return to_route('siprov.index')
                ->with('message', 'Associado e benefício integrados com sucesso na SIPROV.')
                ->with('type', 'success');
        } catch (SiprovException $e) {
            Log::error('Erro de integração SIPROV.', [
                'message' => $e->getMessage(),
                'data'    => $request->all(),
            ]);

            return back()
                ->withErrors([
                    'siprov' => $e->getMessage(),
                ])
                ->withInput();
        } catch (Throwable $e) {
            Log::error('Erro interno ao processar integração SIPROV.', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'data'    => $request->all(),
            ]);

            return back()
                ->withErrors([
                    'siprov' => 'Erro interno ao processar integração SIPROV.',
                ])
                ->withInput();
        }
    }

    private function isValidPhone(?string $phone): bool
    {
        if (empty($phone)) {
            return false;
        }

        $numbers = preg_replace('/\D/', '', $phone);
        $numbers = ltrim($numbers, '55');

        return strlen($numbers) === 11 && $numbers[0] === '9';
    }
}