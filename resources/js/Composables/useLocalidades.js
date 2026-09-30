import estadosJson from '@/Constants/estados.json'

// Busca sem diferenciar maiúsculas nem acentos ("sao" encontra "São Paulo").
export const normalizar = (texto) =>
    String(texto ?? '')
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .trim()

const porNome = (a, b) => a.nome.localeCompare(b.nome, 'pt-BR')

/** Estados (UF) ordenados por nome: [{ codigo, sigla, nome }]. */
export const estados = Object.entries(estadosJson)
    .map(([codigo, { sigla, nome }]) => ({ codigo, sigla, nome }))
    .sort(porNome)

const estadoPorSigla = new Map(estados.map((e) => [e.sigla, e]))

export const buscarEstado = (sigla) => estadoPorSigla.get(String(sigla ?? '').toUpperCase()) ?? null

/** Sigla da UF a partir de sigla ou nome digitado livremente ("sp", "São Paulo" → "SP"). */
export const siglaDoEstado = (texto) => {
    const valor = normalizar(texto)
    if (!valor) return ''

    const estado = estados.find((e) => normalizar(e.sigla) === valor || normalizar(e.nome) === valor)
    return estado?.sigla ?? ''
}

// municipios.json tem ~185 KB: é carregado sob demanda (chunk separado) e
// agrupado por UF uma única vez. O código IBGE do município começa com o da UF.
let municipiosPorUf = null

const carregarTodos = async () => {
    if (!municipiosPorUf) {
        municipiosPorUf = import('@/Constants/municipios.json').then(({ default: municipios }) => {
            const grupos = new Map()

            for (const [codigo, nome] of Object.entries(municipios)) {
                const uf = codigo.slice(0, 2)
                if (!grupos.has(uf)) grupos.set(uf, [])
                grupos.get(uf).push({ codigo, nome })
            }

            for (const lista of grupos.values()) lista.sort(porNome)

            return grupos
        })
    }

    return municipiosPorUf
}

/** Municípios da UF (pela sigla) ordenados por nome: [{ codigo, nome }]. */
export const carregarMunicipios = async (sigla) => {
    const estado = buscarEstado(sigla)
    if (!estado) return []

    const grupos = await carregarTodos()
    return grupos.get(estado.codigo) ?? []
}
