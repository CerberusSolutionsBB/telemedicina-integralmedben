import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { showToast } from '@/Utils/toast'
import { xsrfToken } from './helpers'

/**
 * Templates de SMS do tenant: modal de criação/edição e exclusão.
 */
export function usePaginaSms() {
    const smsModalOpen = ref(false)
    const smsModalTemplate = ref(null)

    const smsDeleteModal = ref(false)
    const smsDeleteItem = ref(null)
    const isDeletingSms = ref(false)

    const openSmsModal = (template = null) => {
        smsModalTemplate.value = template
        smsModalOpen.value = true
    }

    const closeSmsModal = () => {
        smsModalOpen.value = false
        smsModalTemplate.value = null
    }

    const deleteSmsTemplate = (template) => {
        smsDeleteItem.value = template
        smsDeleteModal.value = true
    }

    const closeSmsDeleteModal = () => {
        smsDeleteModal.value = false
        smsDeleteItem.value = null
    }

    const confirmDeleteSmsTemplate = async () => {
        const template = smsDeleteItem.value
        if (!template) return

        isDeletingSms.value = true
        try {
            const response = await fetch(route('forms.sms-templates.destroy', template.id), {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': xsrfToken(),
                },
            })
            const data = await response.json()
            if (response.ok) {
                showToast(data.message || 'Template removido com sucesso.', 'success')
                closeSmsDeleteModal()
                router.reload({ only: ['smsTemplates'] })
            } else {
                showToast(data.message || 'Erro ao remover template.', 'error')
            }
        } catch {
            showToast('Erro ao remover template. Tente novamente.', 'error')
        } finally {
            isDeletingSms.value = false
        }
    }

    return {
        smsModalOpen,
        smsModalTemplate,
        smsDeleteModal,
        smsDeleteItem,
        isDeletingSms,
        openSmsModal,
        closeSmsModal,
        deleteSmsTemplate,
        closeSmsDeleteModal,
        confirmDeleteSmsTemplate,
    }
}
