<?php
namespace App\Http\Controllers\Siprov;

use App\Http\Controllers\Controller;
use App\Models\Siprov;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SiprovIndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $plano  = $request->input('plano');

        $siprovs = Siprov::query()
            ->with('user:id,name,email')

            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('codigo_integracao', 'like', "%{$search}%")
                        ->orWhere('nome_pessoa', 'like', "%{$search}%")
                        ->orWhere('cpf_cnpj', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })

            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })

            ->when($plano, function ($query) use ($plano) {
                $query->where('cod_plano', $plano);
            })

            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Siprov/Index', [
            'siprovs'  => $siprovs,

            'filters'  => [
                'search' => $search,
                'status' => $status,
                'plano'  => $plano,
            ],

            'statuses' => [
                [
                    'label' => 'Pendente',
                    'value' => Siprov::STATUS_PENDING,
                ],
                [
                    'label' => 'Processando',
                    'value' => Siprov::STATUS_PROCESSING,
                ],
                [
                    'label' => 'Sucesso',
                    'value' => Siprov::STATUS_SUCCESS,
                ],
                [
                    'label' => 'Falhou',
                    'value' => Siprov::STATUS_FAILED,
                ],
            ],

            'planos'   => [
                [
                    'label' => 'Clínica Familiar',
                    'value' => 331385,
                ],
                [
                    'label' => 'Clínica Individual',
                    'value' => 331384,
                ],
                [
                    'label' => 'Saúde Mental',
                    'value' => 331386,
                ],
            ],
        ]);
    }
}