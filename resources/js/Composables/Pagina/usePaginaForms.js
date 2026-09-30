import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { showToast } from '@/Utils/toast'
import { firstError } from './helpers'

const reloadForms = () =>
    router.reload({
        only: ['tenant', 'forms', 'fomrs_tenants'],
        preserveScroll: true,
    })

/**
 * Vínculo de formulários ao tenant: vincular, alterar expiração e desvincular.
 */
export function usePaginaForms(props) {
    const availableForms = computed(() => props.forms ?? [])

    const dialogOpen = ref(false)
    const selectedFormIds = ref([])
    const isSavingForms = ref(false)

    const confirmDialogOpen = ref(false)
    const selectedFormToRemove = ref(null)
    const isRemoving = ref(false)

    const syncForms = (selected) => {
        if (!props.tenant?.id) {
            showToast('Tenant não encontrado.', 'error')
            return
        }

        if (!selected?.length) {
            showToast('Selecione ao menos um formulário.', 'warning')
            return
        }

        isSavingForms.value = true

        router.post(route('pagina.configuracao.forms', props.tenant.id), { forms: selected }, {
            preserveScroll: true,
            onSuccess: () => {
                showToast('Formulários vinculados com sucesso!', 'success')
                dialogOpen.value = false
                selectedFormIds.value = []
                reloadForms()
            },
            onError: (errors) => {
                showToast(firstError(errors, 'Erro ao vincular formulários.'), 'error')
            },
            onFinish: () => {
                isSavingForms.value = false
            },
        })
    }

    const handleUpdateExpiresAt = (payload) => {
        router.put(route('pagina.configuracao.expires-at', payload.id), { expires_at: payload.expires_at }, {
            preserveScroll: true,
            onSuccess: () => {
                showToast('Data de expiração atualizada com sucesso!', 'success')
                reloadForms()
            },
            onError: (errors) => {
                showToast(firstError(errors, 'Erro ao atualizar data de expiração.'), 'error')
            },
        })
    }

    const openRemoveLinkDialog = (item) => {
        selectedFormToRemove.value = item
        confirmDialogOpen.value = true
    }

    const closeRemoveLinkDialog = () => {
        confirmDialogOpen.value = false
        selectedFormToRemove.value = null
    }

    const confirmRemoveLink = () => {
        if (!selectedFormToRemove.value?.id) {
            showToast('Formulário vinculado não encontrado.', 'error')
            return
        }

        isRemoving.value = true

        router.delete(route('pagina.configuracao.unlink', selectedFormToRemove.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showToast('Formulário desvinculado com sucesso!', 'success')
                closeRemoveLinkDialog()
                reloadForms()
            },
            onError: () => {
                showToast('Erro ao remover vínculo.', 'error')
            },
            onFinish: () => {
                isRemoving.value = false
            },
        })
    }

    return {
        availableForms,
        dialogOpen,
        selectedFormIds,
        isSavingForms,
        confirmDialogOpen,
        selectedFormToRemove,
        isRemoving,
        syncForms,
        handleUpdateExpiresAt,
        openRemoveLinkDialog,
        closeRemoveLinkDialog,
        confirmRemoveLink,
    }
}
