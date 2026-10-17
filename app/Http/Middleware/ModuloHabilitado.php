<?php

namespace App\Http\Middleware;

use App\Support\ModulosParceiro;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloqueia as telas de um módulo opcional que o admin não ligou para o parceiro
 * (aba Configuração da página do parceiro). Uso: ->middleware('modulo:servicos').
 */
class ModuloHabilitado
{
    public function handle(Request $request, Closure $next, string $modulo): Response
    {
        if (ModulosParceiro::habilitado(tenant('id'), $modulo)) {
            return $next($request);
        }

        $nome = ModulosParceiro::MODULOS[$modulo]['label'] ?? $modulo;
        $mensagem = "O módulo {$nome} não está habilitado para este parceiro. Fale com o administrador.";

        return $request->isMethod('GET')
            ? redirect()->route('tenant.dashboard')->with('error', $mensagem)
            : back()->with('error', $mensagem);
    }
}
