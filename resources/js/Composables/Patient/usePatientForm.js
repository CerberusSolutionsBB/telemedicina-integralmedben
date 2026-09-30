import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { campoDoErro, irParaCampo } from './foco'

const ENDERECO_VAZIO = { cep: '', logradouro: '', numero: '', complemento: '', bairro: '', cidade: '', estado: '' }

/**
 * Dados e persistência do beneficiário (Create/Edit). Sem `patient`, cria.
 * Também protege contra sair da página com alterações não salvas.
 */
export function usePatientForm({ patient = null } = {}) {
    const isEdit = Boolean(patient)

    const enderecos = patient?.enderecos && typeof patient.enderecos === 'object'
        ? Object.fromEntries(Object.keys(ENDERECO_VAZIO).map((k) => [k, patient.enderecos[k] || '']))
        : { ...ENDERECO_VAZIO }

    const form = useForm({
        nome: patient?.nome || '',
        cpf: patient?.cpf || '',
        rg: patient?.rg || '',
        data_nascimento: patient?.data_nascimento || '',
        sexo: patient?.sexo || '',
        email: patient?.email || '',
        numero: patient?.numero || '',
        ...(isEdit ? { status: Boolean(patient.status) } : {}),
        enderecos,
        cod_plano: '',
    })

    const enviando = ref(false)

    const salvar = () => {
        if (form.processing) return

        enviando.value = true
        const options = {
            preserveScroll: true,
            onError: () => {
                const primeiro = Object.keys(form.errors)[0]
                if (primeiro) nextTick(() => irParaCampo(campoDoErro(primeiro)))
            },
            onFinish: () => {
                enviando.value = false
            },
        }

        if (isEdit) {
            form.put(route('patients.update', patient.id), options)
        } else {
            form.post(route('patients.store'), { ...options, onSuccess: () => form.reset() })
        }
    }

    const onBeforeUnload = (event) => {
        if (form.isDirty && !enviando.value) {
            event.preventDefault()
            event.returnValue = ''
        }
    }

    let removerGuarda = null

    onMounted(() => {
        window.addEventListener('beforeunload', onBeforeUnload)
        removerGuarda = router.on('before', (event) => {
            if (form.isDirty && !enviando.value
                && !window.confirm('Você tem alterações não salvas. Deseja sair mesmo assim?')) {
                event.preventDefault()
            }
        })
    })

    onBeforeUnmount(() => {
        window.removeEventListener('beforeunload', onBeforeUnload)
        removerGuarda?.()
    })

    return { form, salvar }
}
