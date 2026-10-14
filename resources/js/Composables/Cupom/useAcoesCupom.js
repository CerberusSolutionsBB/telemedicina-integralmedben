import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'

/**
 * Ações que mudam o cupom (pausar, reativar, cancelar, excluir), sempre com confirmação.
 * `rotas` = { status, destroy } (nomes das rotas). Usado na lista e na edição do cupom.
 */
export function useAcoesCupom(rotas) {
    const processando = ref(false)
    // Ação aguardando confirmação: { cupom, tipo }.
    const pendente = ref(null)

    const status = (c, novo, opcoes) => router.put(route(rotas.status, c.id), { status: novo }, opcoes)

    const ACOES = {
        pausar: {
            rotulo: 'Pausar',
            titulo: 'Pausar cupom',
            mensagem: (c) => `O cupom ${c.codigo} deixa de valer em novas vendas até ser reativado.`,
            aviso: 'Você pode reativá-lo quando quiser.',
            confirmar: 'Sim, pausar',
            variante: 'warning',
            executar: (c, opcoes) => status(c, 'pausado', opcoes),
        },
        reativar: {
            rotulo: 'Reativar',
            titulo: 'Reativar cupom',
            mensagem: (c) => `O cupom ${c.codigo} volta a valer em novas vendas, dentro da validade e dos limites.`,
            aviso: 'Confira a validade antes de divulgar.',
            confirmar: 'Sim, reativar',
            variante: 'info',
            executar: (c, opcoes) => status(c, 'ativo', opcoes),
        },
        cancelar: {
            rotulo: 'Cancelar',
            titulo: 'Cancelar cupom',
            mensagem: (c) => `O cupom ${c.codigo} deixa de valer em novas vendas. Os usos já feitos são mantidos.`,
            aviso: 'O cancelamento não pode ser desfeito.',
            confirmar: 'Sim, cancelar',
            variante: 'danger',
            executar: (c, opcoes) => status(c, 'cancelado', opcoes),
        },
        excluir: {
            rotulo: 'Excluir',
            titulo: 'Excluir cupom',
            mensagem: (c) => `O cupom ${c.codigo} será excluído definitivamente.`,
            aviso: 'Esta ação não pode ser desfeita.',
            confirmar: 'Sim, excluir',
            variante: 'danger',
            executar: (c, opcoes) => router.delete(route(rotas.destroy, c.id), opcoes),
        },
    }

    const acaoPendente = computed(() => (pendente.value ? ACOES[pendente.value.tipo] : null))

    const pedir = (cupom, tipo) => (pendente.value = { cupom, tipo })

    const fechar = () => (pendente.value = null)

    const confirmar = () => {
        const { cupom, tipo } = pendente.value
        processando.value = true
        ACOES[tipo].executar(cupom, {
            preserveScroll: true,
            onFinish: () => {
                processando.value = false
                pendente.value = null
            },
        })
    }

    return { ACOES, pendente, acaoPendente, processando, pedir, fechar, confirmar }
}
