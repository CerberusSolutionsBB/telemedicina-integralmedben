<?php

namespace App\Http\Controllers\Tenant;

use App\Models\Convenio;

/**
 * Convênios do parceiro (painel do tenant), auditados.
 */
class ConvenioController extends CadastroIconeController
{
    protected string $model = Convenio::class;

    protected string $paginas = 'Tenant/Convenio';

    protected string $rotas = 'convenios';

    protected string $plural = 'Convênios';

    protected string $singular = 'Convênio';

    protected string $novo = 'Novo convênio';
}
