/** Rótulos e cores da situação efetiva do cupom (calculada no backend). */
export const SITUACOES = {
    ativo: { label: 'Ativo', classe: 'bg-green-50 text-green-700 border-green-200' },
    agendado: { label: 'Agendado', classe: 'bg-blue-50 text-blue-700 border-blue-200' },
    pausado: { label: 'Pausado', classe: 'bg-amber-50 text-amber-700 border-amber-200' },
    esgotado: { label: 'Esgotado', classe: 'bg-gray-100 text-gray-600 border-gray-200' },
    expirado: { label: 'Expirado', classe: 'bg-gray-100 text-gray-600 border-gray-200' },
    cancelado: { label: 'Cancelado', classe: 'bg-red-50 text-red-700 border-red-200' },
}

export const fmtMoeda = (valor) => Number(valor || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })

export const fmtDesconto = (cupom) =>
    cupom.tipo === 'percentual' ? `${Number(cupom.valor).toLocaleString('pt-BR')}%` : fmtMoeda(cupom.valor)
