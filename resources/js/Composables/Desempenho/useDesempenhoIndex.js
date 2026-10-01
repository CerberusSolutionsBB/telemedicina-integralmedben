import { computed, reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { useFlashToast } from '@/Composables/Patient/useFlashToast'

const normalizar = (texto) => String(texto ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim()

// Abas por status (rótulos vêm do servidor em statusLabels).
const ABAS = ['todas', 'em_andamento', 'atingida', 'nao_iniciada', 'encerrada']

/**
 * Listagem de metas: abas por status, busca/filtros e exclusão com confirmação.
 */
export function useDesempenhoIndex(props) {
    useFlashToast()

    const aba = ref('todas')
    const filtros = reactive({ busca: '', tipo: '', plano: '' })

    const contagemPorAba = computed(() =>
        Object.fromEntries(ABAS.map((key) => [
            key,
            key === 'todas' ? props.desempenhos.length : props.desempenhos.filter((d) => d.progresso.status === key).length,
        ])),
    )

    const planosDisponiveis = computed(() => [...new Set(props.desempenhos.map((d) => d.plano))].sort())

    const filtrados = computed(() => {
        const busca = normalizar(filtros.busca)

        return props.desempenhos.filter((d) =>
            (aba.value === 'todas' || d.progresso.status === aba.value)
            && (!filtros.tipo || d.tipo_meta === filtros.tipo)
            && (!filtros.plano || d.plano === filtros.plano)
            && (!busca || normalizar(`${d.titulo} ${d.roles.join(' ')}`).includes(busca)),
        )
    })

    const excluirModal = ref({ show: false, desempenho: null, isProcessing: false })

    const abrirExcluir = (desempenho) => {
        excluirModal.value = { show: true, desempenho, isProcessing: false }
    }

    const fecharExcluir = () => {
        excluirModal.value.show = false
    }

    const confirmarExcluir = () => {
        const desempenho = excluirModal.value.desempenho
        if (!desempenho) return

        excluirModal.value.isProcessing = true
        router.delete(route('desempenho.destroy', desempenho.id), {
            preserveScroll: true,
            onSuccess: fecharExcluir,
            onFinish: () => { excluirModal.value.isProcessing = false },
        })
    }

    const novaMeta = () => router.visit(route('desempenho.create'))

    return {
        abas: ABAS,
        aba,
        filtros,
        contagemPorAba,
        planosDisponiveis,
        filtrados,
        excluirModal,
        abrirExcluir,
        fecharExcluir,
        confirmarExcluir,
        novaMeta,
    }
}
