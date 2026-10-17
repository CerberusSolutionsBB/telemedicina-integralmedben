import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { maiuscula, rotaDe } from './modulos'

/**
 * Ações que mudam o item (ativar, inativar, excluir), sempre com confirmação.
 */
export function useAcoesCadastroIcone(modulo) {
    const processando = ref(false)
    // Ação aguardando confirmação: { item, tipo }.
    const pendente = ref(null)

    const nome = maiuscula(modulo.singular)

    const ACOES = {
        ativar: {
            titulo: `Ativar ${modulo.singular}`,
            mensagem: (i) => `${nome} "${i.titulo}" volta a aparecer como disponível.`,
            aviso: 'A alteração fica registrada no histórico.',
            confirmar: 'Sim, ativar',
            variante: 'info',
            executar: (i, opcoes) => router.put(route(rotaDe(modulo, 'status'), i.id), { status: true }, opcoes),
        },
        inativar: {
            titulo: `Inativar ${modulo.singular}`,
            mensagem: (i) => `${nome} "${i.titulo}" deixa de aparecer como disponível.`,
            aviso: 'Você pode ativá-lo de novo quando quiser.',
            confirmar: 'Sim, inativar',
            variante: 'warning',
            executar: (i, opcoes) => router.put(route(rotaDe(modulo, 'status'), i.id), { status: false }, opcoes),
        },
        excluir: {
            titulo: `Excluir ${modulo.singular}`,
            mensagem: (i) => `${nome} "${i.titulo}" será excluído definitivamente.`,
            aviso: 'Esta ação não pode ser desfeita. O histórico continua na auditoria.',
            confirmar: 'Sim, excluir',
            variante: 'danger',
            executar: (i, opcoes) => router.delete(route(rotaDe(modulo, 'destroy'), i.id), opcoes),
        },
    }

    const acaoPendente = computed(() => (pendente.value ? ACOES[pendente.value.tipo] : null))

    const pedir = (item, tipo) => (pendente.value = { item, tipo })

    const fechar = () => (pendente.value = null)

    const confirmar = () => {
        const { item, tipo } = pendente.value
        processando.value = true
        ACOES[tipo].executar(item, {
            preserveScroll: true,
            onFinish: () => {
                processando.value = false
                pendente.value = null
            },
        })
    }

    return { pendente, acaoPendente, processando, pedir, fechar, confirmar }
}
