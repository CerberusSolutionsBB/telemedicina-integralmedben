import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { showToast } from '@/Utils/toast'
import { firstError } from './helpers'

const getSiprovKey = (item) => `${item.codPessoa}-${item.codBeneficio}`

/**
 * Aba de telemedicina: itens vinculados, desvínculo e busca/vínculo de associados SIPROV.
 */
export function useTelemedicina(props) {
    const isSavingTelemedicina = ref(false)
    const telemedicinaSearch = ref('')
    const selectedTelemedicinaIds = ref([])

    const telemedicinaUnlinkModal = ref(false)
    const telemedicinaUnlinkItem = ref(null)
    const isUnlinkingTelemedicina = ref(false)

    const siprovModalOpen = ref(false)
    const siprovSearch = ref('')
    const siprovResults = ref([])
    const siprovSelected = ref([])
    const siprovError = ref(null)
    const siprovPage = ref(1)
    const siprovHasNext = ref(false)
    const siprovTotal = ref(0)
    const isSearchingSiprov = ref(false)
    const isSavingSiprov = ref(false)

    const filteredTelemedicinaVinculados = computed(() => {
        const query = telemedicinaSearch.value.toLowerCase().trim()
        if (!query) return props.telemedicinaVinculados
        return props.telemedicinaVinculados.filter(item => {
            const data = item.data || {}
            return (data.title || '').toLowerCase().includes(query)
                || (data.cpf_cnpj || '').toLowerCase().includes(query)
                || (data.plano_label || '').toLowerCase().includes(query)
                || (data.codigo_integracao || '').toLowerCase().includes(query)
        })
    })

    const linkedTelemedicinaIds = computed(() =>
        props.telemedicinaVinculados.map(v => v.data?.question_id).filter(Boolean),
    )

    const syncTelemedicina = () => {
        if (!props.tenant?.id) {
            showToast('Tenant não encontrado.', 'error')
            return
        }

        isSavingTelemedicina.value = true

        router.put(
            route('pagina.configuracao.telemedicina', props.tenant.id),
            { enabled: true, questions: selectedTelemedicinaIds.value },
            {
                preserveScroll: true,
                onSuccess: () => {
                    showToast('Telemedicina atualizada com sucesso!', 'success')
                    selectedTelemedicinaIds.value = []
                    router.reload({
                        only: ['tenant', 'telemedicinaEnabled', 'telemedicinaQuestions', 'telemedicinaVinculados'],
                        preserveScroll: true,
                    })
                },
                onError: () => {
                    showToast('Erro ao atualizar telemedicina.', 'error')
                },
                onFinish: () => {
                    isSavingTelemedicina.value = false
                },
            },
        )
    }

    const unlinkTelemedicina = (item) => {
        telemedicinaUnlinkItem.value = item
        telemedicinaUnlinkModal.value = true
    }

    const closeUnlinkTelemedicina = () => {
        telemedicinaUnlinkModal.value = false
        telemedicinaUnlinkItem.value = null
    }

    const confirmUnlinkTelemedicina = () => {
        const item = telemedicinaUnlinkItem.value
        if (!props.tenant?.id || !item) return

        isUnlinkingTelemedicina.value = true

        router.delete(
            route('pagina.configuracao.telemedicina.unlink', { tenant: props.tenant.id, telemedicinaTenant: item.id }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    showToast('Item desvinculado com sucesso!', 'success')
                    closeUnlinkTelemedicina()
                    router.reload({ only: ['telemedicinaVinculados', 'planoUso'], preserveScroll: true })
                },
                onError: () => {
                    showToast('Erro ao desvincular item.', 'error')
                },
                onFinish: () => {
                    isUnlinkingTelemedicina.value = false
                },
            },
        )
    }

    const searchSiprov = async (page = 1) => {
        isSearchingSiprov.value = true
        siprovError.value = null
        try {
            const params = new URLSearchParams()
            if (siprovSearch.value) params.append('q', siprovSearch.value)
            if (page > 1) params.append('pagina', page)

            const response = await fetch(route('pagina.configuracao.telemedicina.searchSiprov') + '?' + params.toString())
            if (!response.ok) {
                throw new Error('Erro ao buscar associados.')
            }
            const data = await response.json()

            let itens = data.itens ?? data
            if (Array.isArray(itens) && itens.length === 1 && itens[0].itens) {
                itens = itens[0].itens
            }
            siprovResults.value = Array.isArray(itens) ? itens : []

            siprovPage.value = data.paginaAtual ?? 1
            siprovHasNext.value = data.proximaPagina ?? false
            siprovTotal.value = data.quantidade ?? siprovResults.value.length
        } catch {
            siprovResults.value = []
            siprovError.value = 'Não foi possível conectar à SIPROV. Tente novamente.'
        } finally {
            isSearchingSiprov.value = false
        }
    }

    const goToSiprovPage = (page) => {
        if (page < 1) return
        searchSiprov(page)
    }

    const openSiprovModal = () => {
        siprovModalOpen.value = true
        siprovSelected.value = []
        siprovError.value = null
        siprovPage.value = 1
        searchSiprov(1)
    }

    const toggleSiprovItem = (item) => {
        const key = getSiprovKey(item)
        const idx = siprovSelected.value.indexOf(key)
        if (idx >= 0) {
            siprovSelected.value.splice(idx, 1)
        } else {
            siprovSelected.value.push(key)
        }
    }

    const toggleSelectAllSiprov = () => {
        if (siprovSelected.value.length === siprovResults.value.length) {
            siprovSelected.value = []
        } else {
            siprovSelected.value = siprovResults.value.map(r => getSiprovKey(r))
        }
    }

    const vincularSiprov = () => {
        if (!props.tenant?.id || !siprovSelected.value.length) return

        isSavingSiprov.value = true

        const selectedItems = siprovResults.value
            .filter(r => siprovSelected.value.includes(getSiprovKey(r)))
            .map(({ codPessoa, nomePessoa, cpfCnpj, planos, codBeneficio, email, dataNascimento, telefoneCelular, sexo }) => ({
                codPessoa, nomePessoa, cpfCnpj, planos, codBeneficio, email, dataNascimento, telefoneCelular, sexo,
            }))

        router.put(
            route('pagina.configuracao.telemedicina', props.tenant.id),
            { enabled: true, siprov_items: selectedItems },
            {
                preserveScroll: true,
                onSuccess: () => {
                    showToast('Itens SIPROV vinculados com sucesso!', 'success')
                    siprovModalOpen.value = false
                    siprovSelected.value = []
                    router.reload({ only: ['telemedicinaVinculados', 'planoUso'], preserveScroll: true })
                },
                onError: (errors) => {
                    showToast(firstError(errors, 'Erro ao vincular itens SIPROV.'), 'error')
                },
                onFinish: () => {
                    isSavingSiprov.value = false
                },
            },
        )
    }

    return {
        isSavingTelemedicina,
        telemedicinaSearch,
        selectedTelemedicinaIds,
        telemedicinaUnlinkModal,
        telemedicinaUnlinkItem,
        isUnlinkingTelemedicina,
        siprovModalOpen,
        siprovSearch,
        siprovResults,
        siprovSelected,
        siprovError,
        siprovPage,
        siprovHasNext,
        siprovTotal,
        isSearchingSiprov,
        isSavingSiprov,
        filteredTelemedicinaVinculados,
        linkedTelemedicinaIds,
        syncTelemedicina,
        unlinkTelemedicina,
        closeUnlinkTelemedicina,
        confirmUnlinkTelemedicina,
        searchSiprov,
        goToSiprovPage,
        openSiprovModal,
        getSiprovKey,
        toggleSiprovItem,
        toggleSelectAllSiprov,
        vincularSiprov,
    }
}
