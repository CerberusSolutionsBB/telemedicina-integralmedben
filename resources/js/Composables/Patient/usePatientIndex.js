import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { showToast } from '@/Utils/toast'
import { useFlashToast } from './useFlashToast'

/**
 * Listagem de beneficiários: abas, importação e exclusão com confirmação.
 */
export function usePatientIndex(props) {
    useFlashToast()

    const activeTab = ref('current')
    const openImportDialog = ref(false)

    const currentPatientsCount = computed(() => props.patients?.total ?? props.patients?.data?.length ?? 0)
    const newPatientsCount = computed(() => props.newPatients?.total ?? props.newPatients?.data?.length ?? 0)

    const novoPaciente = () => router.visit(route('patients.create'))

    const deleteModal = ref({ show: false, patient: null, isProcessing: false })

    const confirmDelete = (patient) => {
        deleteModal.value = { show: true, patient, isProcessing: false }
    }

    const cancelDelete = () => {
        deleteModal.value.show = false
    }

    const confirmDeletePatient = () => {
        deleteModal.value.isProcessing = true
        router.delete(route('patients.destroy', deleteModal.value.patient.id), {
            preserveScroll: true,
            onSuccess: () => {
                deleteModal.value.show = false
                deleteModal.value.patient = null
            },
            onError: (errors) => {
                showToast(Object.values(errors).flat()[0] || 'Erro ao excluir paciente.', 'error')
                deleteModal.value.show = false
            },
            onFinish: () => {
                deleteModal.value.isProcessing = false
            },
        })
    }

    return {
        activeTab,
        openImportDialog,
        currentPatientsCount,
        newPatientsCount,
        novoPaciente,
        deleteModal,
        confirmDelete,
        cancelDelete,
        confirmDeletePatient,
    }
}
