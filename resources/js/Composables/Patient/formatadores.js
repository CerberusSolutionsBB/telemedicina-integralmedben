// Formatação de dados do beneficiário para exibição.

export const formatarDataHora = (data) => {
    if (!data) return '-'
    return new Date(data).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

export const formatarCpf = (cpf) => {
    if (!cpf) return '-'
    const digitos = cpf.replace(/\D/g, '')
    if (digitos.length !== 11) return cpf
    return digitos.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4')
}

// "Rua X, 10 - Apto 1, Bairro, Cidade/UF, CEP: 00000-000" (ou null se vazio).
export const formatarEndereco = (e) => {
    if (!e) return null
    const partes = []
    if (e.logradouro) {
        let linha = e.logradouro
        if (e.numero) linha += `, ${e.numero}`
        if (e.complemento) linha += ` - ${e.complemento}`
        partes.push(linha)
    }
    if (e.bairro) partes.push(e.bairro)
    const cidadeEstado = [e.cidade, e.estado].filter(Boolean)
    if (cidadeEstado.length) partes.push(cidadeEstado.join('/'))
    if (e.cep) partes.push(`CEP: ${e.cep}`)
    return partes.length ? partes.join(', ') : null
}
