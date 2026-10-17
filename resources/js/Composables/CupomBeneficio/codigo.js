// Mesmas regras de CupomBeneficio::gerarCodigo (sem 0/O e 1/I/L, 8 caracteres).
const ALFABETO = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789'
const TAMANHO = 8

/**
 * Sugestão de código para um cupom novo. A unicidade é garantida pelo servidor
 * (se repetir, a validação avisa e "Gerar outro" resolve).
 */
export function gerarCodigoCupom() {
    const aleatorios = crypto.getRandomValues(new Uint32Array(TAMANHO))
    return Array.from(aleatorios, (n) => ALFABETO[n % ALFABETO.length]).join('')
}
