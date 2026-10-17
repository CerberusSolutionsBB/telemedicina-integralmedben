import { shallowRef } from 'vue'

// Carregado uma única vez por página e compartilhado entre os componentes.
export const iconesLucide = shallowRef(null)
let carregamento = null

/**
 * Carrega sob demanda todos os ícones do lucide (nome => componente) em iconesLucide.
 */
export function carregarIconesLucide() {
    carregamento ??= import('./iconesLucide').then((modulo) => (iconesLucide.value = modulo.icons))

    return carregamento
}
