<?php

namespace App\Http\Controllers\Tenant;

use App\Models\Servico;

/**
 * Serviços oferecidos pelo parceiro (painel do tenant), auditados.
 */
class ServicoController extends CadastroIconeController
{
    protected string $model = Servico::class;

    protected string $paginas = 'Tenant/Servico';

    protected string $rotas = 'servicos';

    protected string $plural = 'Serviços';

    protected string $singular = 'Serviço';

    protected string $novo = 'Novo serviço';
}
