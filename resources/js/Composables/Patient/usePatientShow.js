import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { showToast } from '@/Utils/toast'
import { formatarEndereco } from './formatadores'
import { toastDaVisita } from './useFlashToast'

/**
 * Detalhes do beneficiário: status, endereço e reenvio de SMS.
 */
export function usePatientShow(props) {
    const isActive = computed(() => Boolean(props.patient.status))
    const enderecoFormatado = computed(() => formatarEndereco(props.patient.enderecos))

    const logToResend = ref(null)
    const resendLogDialogOpen = ref(false)

    const openResendLogDialog = (log) => {
        logToResend.value = log
        resendLogDialogOpen.value = true
    }

    const resendLog = () => {
        if (!logToResend.value) return
        router.post(route('patients.sms-logs.resend', [props.patient.id, logToResend.value.id]), {}, {
            preserveScroll: true,
            onSuccess: toastDaVisita,
            onError: () => showToast('Erro ao reenviar SMS.', 'error'),
        })
    }

    const toggleStatus = () => {
        router.patch(route('patients.toggle-status', props.patient.id), {}, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: toastDaVisita,
        })
    }

    return {
        isActive,
        enderecoFormatado,
        logToResend,
        resendLogDialogOpen,
        openResendLogDialog,
        resendLog,
        toggleStatus,
    }
}
