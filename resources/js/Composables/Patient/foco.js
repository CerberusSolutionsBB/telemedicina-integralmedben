// Navegação até campos do formulário de beneficiário (pendências e erros).

const prefereMenosMovimento = () =>
    typeof window !== 'undefined' && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches

export const rolar = (el, block = 'center') =>
    el?.scrollIntoView({ behavior: prefereMenosMovimento() ? 'auto' : 'smooth', block })

export const irParaCampo = (id) => {
    const el = document.getElementById(id)
    if (!el) return
    rolar(el)
    el.focus({ preventScroll: true })
}

// Chave de erro do servidor → id do campo na tela.
const CAMPO_DO_ERRO = {
    'enderecos.cep': 'cep',
    'enderecos.logradouro': 'logradouro',
    'enderecos.numero': 'numero_endereco',
    'enderecos.complemento': 'complemento',
    'enderecos.bairro': 'bairro',
    'enderecos.cidade': 'cidade',
    'enderecos.estado': 'uf',
}

export const campoDoErro = (chave) => CAMPO_DO_ERRO[chave] ?? chave
