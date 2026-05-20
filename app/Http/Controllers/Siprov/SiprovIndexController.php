<?php
namespace App\Http\Controllers\Siprov;

use App\Data\SiprovAssociadoQueryData;
use App\Http\Controllers\Controller;
use App\Services\Siprov\SiprovAssociadoService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SiprovIndexController extends Controller
{
    public function __construct(
        private readonly SiprovAssociadoService $siprovService,
    ) {}

    public function __invoke(Request $request): Response
    {
        $situacaoBeneficio = $request->input('situacaoBeneficio', 'Ativo');
        $data = SiprovAssociadoQueryData::fromSituacaoBeneficio($situacaoBeneficio);

        $associados = $this->siprovService->query($data);

        return Inertia::render('Siprov/Index', [
            'associados' => $associados,
        ]);
    }
}
