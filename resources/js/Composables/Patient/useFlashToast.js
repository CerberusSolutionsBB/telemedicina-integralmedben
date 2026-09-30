import { watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { showToast } from '@/Utils/toast'

/** Exibe como toast as mensagens flash (success/error) da página. */
export function useFlashToast() {
    const page = usePage()

    watch(() => page.props.flash?.success, (msg) => {
        if (msg) showToast(msg, 'success')
    })

    watch(() => page.props.flash?.error, (msg) => {
        if (msg) showToast(msg, 'error')
    })
}

/** Toast da flash de uma visita específica (callback onSuccess do router). */
export const toastDaVisita = (visitedPage) => {
    const { success, error } = visitedPage.props.flash ?? {}
    if (success) showToast(success, 'success')
    else if (error) showToast(error, 'error')
}
