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

    // Quem registrou o plano: usuário, ou o motivo de não haver um.
    const registradoPor = computed(() => {
        const plano = props.registro?.plano
        if (!plano) return null
        if (plano.usuario) return plano.usuario
        return plano.origem_tipo === 'formulario_publico' ? 'Formulário público (sem login)' : 'Não registrado'
    })

    // Quem criou o beneficiário: usuário; sem login quando auditado sem usuário;
    // "Não registrado" para cadastros anteriores à auditoria.
    const criadoPor = computed(() => {
        const cadastro = props.registro?.cadastro ?? {}
        if (cadastro.usuario) return cadastro.usuario
        return cadastro.auditado ? 'Sem login (formulário ou importação)' : 'Não registrado'
    })

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
        registradoPor,
        criadoPor,
        logToResend,
        resendLogDialogOpen,
        openResendLogDialog,
        resendLog,
        toggleStatus,
    }
}
