import { gerarCodigoCupom } from '../CupomBeneficio/codigo'

/**
 * Cadastros do parceiro com título, descrição, ícone e status. Cada módulo define
 * o prefixo das rotas e os textos; telas e componentes são os mesmos (CadastroIcone/*).
 */
export const SERVICOS = {
    rota: 'servicos',
    singular: 'serviço',
    plural: 'serviços',
    titulo: 'Serviços',
    novo: 'Novo serviço',
    descricaoLista: 'Serviços oferecidos pelo parceiro. Toda alteração fica registrada no histórico.',
    exemploTitulo: 'Ex.: Teleconsulta com clínico geral',
    exemploDescricao: 'Ex.: Atendimento por vídeo, 24 horas, sem custo adicional.',
}

export const CONVENIOS = {
    rota: 'convenios',
    singular: 'convênio',
    plural: 'convênios',
    titulo: 'Convênios',
    novo: 'Novo convênio',
    descricaoLista: 'Convênios do parceiro. Toda alteração fica registrada no histórico.',
    exemploTitulo: 'Ex.: Farmácia Bem Estar',
    exemploDescricao: 'Ex.: 15% de desconto em medicamentos apresentando o cartão.',
}

export const CUPONS_BENEFICIO = {
    rota: 'cupons-beneficio',
    singular: 'cupom',
    plural: 'cupons de benefício',
    titulo: 'Cupons de benefício',
    novo: 'Novo cupom de benefício',
    descricaoLista: 'Descontos que o beneficiário usa nos convênios e serviços do parceiro. Toda alteração fica registrada no histórico.',
    exemploTitulo: 'Ex.: 15% na Farmácia Bem Estar',
    buscaPlaceholder: 'Buscar cupom pelo código, título ou descrição',
    // Gera o voucher em PDF (rota cupons-beneficio.pdf), como o cupom de venda.
    pdf: true,
    exemploDescricao: 'Ex.: Apresente o cartão do beneficiário no caixa. Não cumulativo com outras promoções.',
    // Seções do formulário além de "Dados", "Ícone" e "Status" (Desconto, Onde vale).
    secoesExtras: 2,
    // Campos além de título, descrição, ícone e status.
    valoresIniciais: (item) => ({
        // Cupom novo já vem com um código sugerido (pode ser trocado ou gerado de novo).
        codigo: item?.codigo ?? gerarCodigoCupom(),
        cod_plano: item?.cod_plano ?? '',
        convenio_id: item?.convenio_id ?? '',
        servico_id: item?.servico_id ?? '',
        tipo_desconto: item?.tipo_desconto ?? 'percentual',
        valor_desconto: item?.valor_desconto ?? '',
        prazo: item?.prazo ?? '',
    }),
}

/** 0 -> "Sem serviços", 1 -> "1 serviço", 2 -> "2 serviços". */
const quantidadeServicos = (total) => (total ? `${total} ${total === 1 ? 'serviço' : 'serviços'}` : 'Sem serviços')

export const PROCESSOS = {
    rota: 'processos',
    singular: 'processo',
    plural: 'processos',
    titulo: 'Processos',
    novo: 'Novo processo',
    descricaoLista: 'Processos do parceiro. Toda alteração fica registrada no histórico.',
    exemploTitulo: 'Ex.: Abertura de prontuário',
    exemploDescricao: 'Ex.: Etapas e documentos necessários para abrir o prontuário do beneficiário.',
    // Lista sem a coluna "Detalhes" (descrição fica no detalhe)...
    semDetalhes: true,
    // ...e com colunas próprias entre o título e o status.
    colunas: [
        { titulo: 'Valor', valor: (item) => item.valor_formatado, classe: 'text-right tabular-nums' },
        {
            titulo: 'Qtd. de serviços',
            valor: (item) => item.servicos?.length ?? 0,
            // No cartão do celular: "2 serviços".
            texto: (item) => quantidadeServicos(item.servicos?.length ?? 0),
            classe: 'text-center tabular-nums',
        },
    ],
    // Seção do formulário além de "Dados" e "Ícone" (Valor); Serviços fica no lugar de "Status".
    secoesExtras: 1,
    // Campos além de título, descrição, ícone e status.
    valoresIniciais: (item) => ({
        valor: item?.valor ?? '',
        servicos: item?.servicos?.map((servico) => servico.id) ?? [],
    }),
}

/** Nome da rota do módulo (ex.: rotaDe(SERVICOS, 'show') -> 'servicos.show'). */
export const rotaDe = (modulo, acao) => `${modulo.rota}.${acao}`

/** "serviço" -> "Serviço". */
export const maiuscula = (texto) => texto.charAt(0).toUpperCase() + texto.slice(1)
