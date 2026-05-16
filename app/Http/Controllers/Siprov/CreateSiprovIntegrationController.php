<?php
namespace App\Http\Controllers\Siprov;

use App\Data\SiprovIntegrationData;
use App\Exceptions\SiprovException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Siprov\CreateSiprovIntegrationRequest;
use App\Services\Siprov\SiprovIntegrationService;
use Illuminate\Http\JsonResponse;
use Throwable;

class CreateSiprovIntegrationController extends Controller
{
    public function __invoke(
        CreateSiprovIntegrationRequest $request,
        SiprovIntegrationService $service
    ): JsonResponse {
        try {
            $data = SiprovIntegrationData::fromRequest($request);

            $result = $service->execute($data);

            return response()->json([
                'success' => true,
                'message' => 'Associado e benefício integrados com sucesso na SIPROV.',
                'data'    => $result,
            ]);
        } catch (SiprovException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro na integração com a SIPROV.',
                'error'   => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro interno ao processar integração SIPROV.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
