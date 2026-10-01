import { computed, reactive, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'

const PARAMS = { busca: 'hist_busca', plano: 'hist_plano', de: 'hist_de', ate: 'hist_ate' }

const VAZIO = { busca: '', plano: '', de: '', ate: '' }

/**
 * Filtros do histórico de registros dos planos. Ficam na URL (?hist_*), então
 * sobrevivem ao recarregar; cada mudança recarrega só o histórico.
 */
export function usePaginaHistorico(props) {
    const filtros = reactive({ ...VAZIO, ...(props.planoRegistrosFiltros ?? {}) })
    const carregando = ref(false)
    let timer = null

    const temFiltro = computed(() => Object.values(filtros).some((v) => String(v).trim() !== ''))

    const aplicar = () => {
        // A partir da URL atual: preserva ?aba= e ?planos= e remove filtro vazio.
        const url = new URL(window.location.href)
        for (const [campo, param] of Object.entries(PARAMS)) {
            const valor = String(filtros[campo] ?? '').trim()
            if (valor) url.searchParams.set(param, valor)
            else url.searchParams.delete(param)
        }

        router.get(url.pathname + url.search, {}, {
            only: ['planoRegistros', 'planoRegistrosTotais', 'planoRegistrosFiltros'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => { carregando.value = true },
            onFinish: () => { carregando.value = false },
        })
    }

    // Só o texto mudou: espera a digitação parar. Plano, datas ou "Limpar"
    // (vários campos de uma vez): aplica logo, numa única recarga.
    watch(() => ({ ...filtros }), (novo, antigo) => {
        clearTimeout(timer)
        const soBusca = ['plano', 'de', 'ate'].every((campo) => novo[campo] === antigo[campo])
        if (soBusca) timer = setTimeout(aplicar, 300)
        else aplicar()
    })

    // Card de plano: filtra a tabela por ele; clicar de novo remove o filtro.
    const alternarPlano = (codPlano) => {
        filtros.plano = filtros.plano === codPlano ? '' : codPlano
    }

    const limparFiltros = () => {
        clearTimeout(timer)
        Object.assign(filtros, VAZIO)
    }

    return { filtros, carregando, temFiltro, alternarPlano, limparFiltros }
}
