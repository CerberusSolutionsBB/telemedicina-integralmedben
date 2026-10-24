import { shallowRef } from 'vue'

// Carregado uma única vez por página e compartilhado entre os componentes.
export const iconesLucide = shallowRef(null)
let carregamento = null

/** "HeartPulse" -> "Heart Pulse". */
export const separarNomeIcone = (nome) => String(nome ?? '').replace(/([a-z0-9])([A-Z])/g, '$1 $2')

/** Rótulo do ícone: o dos sugeridos ([{ value, label }], em português) ou o nome separado. */
export const rotuloIcone = (nome, sugeridos = []) => sugeridos.find((s) => s.value === nome)?.label ?? separarNomeIcone(nome)

/**
 * Carrega sob demanda todos os ícones do lucide (nome => componente) em iconesLucide.
 */
export function carregarIconesLucide() {
    carregamento ??= import('./iconesLucide').then((modulo) => (iconesLucide.value = modulo.icons))

    return carregamento
}
