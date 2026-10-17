const moeda = (valor) => Number(valor || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })

const dataBr = (iso) => (iso ? iso.split('-').reverse().join('/') : '')

/** "15%" ou "R$ 30,00" a partir do tipo e do valor. */
export const descontoTexto = (tipo, valor) =>
    !valor ? '' : tipo === 'valor_fixo' ? moeda(valor) : `${Number(valor).toLocaleString('pt-BR')}%`

/**
 * O que o cupom dá, numa frase simples:
 * "Beneficiários do plano Clínica Familiar ganham 15% de desconto em Farmácia X, até 31/12/2026.
 *  Basta apresentar o código K7M3QX9P."
 * Partes ainda não preenchidas viram um aviso do que falta.
 */
export function fraseCupom({ tipo, valor, plano, convenio, servico, prazo, codigo }) {
    const desconto = descontoTexto(tipo, valor)
    const onde = [convenio, servico].filter(Boolean).join(' e em ')

    if (!desconto || !onde) {
        const faltando = [!desconto && 'o valor do desconto', !onde && 'o convênio ou o serviço'].filter(Boolean)
        return { completa: false, texto: `Preencha ${faltando.join(' e ')} para ver como o cupom fica.` }
    }

    const quem = plano ? `Beneficiários do plano ${plano}` : 'Beneficiários de qualquer plano'
    const ate = prazo ? `, até ${dataBr(prazo)}` : ', sem prazo para acabar'

    const comCodigo = codigo ? ` Basta apresentar o código ${codigo}.` : ''

    return { completa: true, texto: `${quem} ganham ${desconto} de desconto em ${onde}${ate}.${comCodigo}` }
}
