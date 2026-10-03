import { computed, reactive } from 'vue'

const DIA_MS = 24 * 60 * 60 * 1000

/**
 * Filtros e links de download do relatório de produção por usuário.
 * `opcoes.padrao` traz o período inicial (mês atual até hoje).
 */
export function useRelatorioProducao(opcoes) {
    const filtros = reactive({
        de: opcoes.padrao?.de ?? '',
        ate: opcoes.padrao?.ate ?? '',
        usuario: '',
        perfil: '',
        plano: '',
        origem: '',
    })

    // Mesmas regras do servidor (RelatorioProducaoController::validar).
    const erroPeriodo = computed(() => {
        if (!filtros.de || !filtros.ate) return 'Informe o período.'
        const dias = (new Date(filtros.ate) - new Date(filtros.de)) / DIA_MS
        if (dias < 0) return 'A data final deve ser igual ou posterior à inicial.'
        if (dias >= opcoes.maxDias) return `O período pode ter no máximo ${opcoes.maxDias} dias.`
        return null
    })

    const params = computed(() =>
        Object.fromEntries(Object.entries(filtros).filter(([, valor]) => valor !== '' && valor !== null)),
    )

    const url = (formato) => route('relatorios.producao', { formato, ...params.value })

    const limpar = () => {
        Object.assign(filtros, { de: opcoes.padrao?.de ?? '', ate: opcoes.padrao?.ate ?? '', usuario: '', perfil: '', plano: '', origem: '' })
    }

    return { filtros, erroPeriodo, url, limpar }
}
