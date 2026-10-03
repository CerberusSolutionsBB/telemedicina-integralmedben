<?php

namespace App\Http\Middleware;

use App\Support\BeneficiarioPermissoes;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloqueia a ação do CRUD de beneficiários quando o parceiro não a habilitou
 * (aba Beneficiário da página do parceiro). Uso: ->middleware('beneficiario:edit').
 */
class BeneficiarioPermite
{
    public function handle(Request $request, Closure $next, string $acao): Response
    {
        if (BeneficiarioPermissoes::permite(tenant('id'), $acao)) {
            return $next($request);
        }

        $mensagem = 'Ação "'.(BeneficiarioPermissoes::ACOES[$acao] ?? $acao).'" de beneficiário não está habilitada para este parceiro.';

        return $request->isMethod('GET')
            ? redirect()->route('patients.index')->with('error', $mensagem)
            : back()->with('error', $mensagem);
    }
}
