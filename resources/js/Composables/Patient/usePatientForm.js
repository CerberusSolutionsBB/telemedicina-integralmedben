import { nextTick, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useSairSemSalvar } from '@/Composables/useSairSemSalvar'
import { campoDoErro, irParaCampo } from './foco'

const ENDERECO_VAZIO = { cep: '', logradouro: '', numero: '', complemento: '', bairro: '', cidade: '', estado: '' }

/**
 * Dados e persistência do beneficiário (Create/Edit). Sem `patient`, cria.
 * Também protege contra sair da página com alterações não salvas (useSairSemSalvar).
 */
export function usePatientForm({ patient = null, familiares = [] } = {}) {
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
        // Vendedor/indicado por: quem recebe a comissão da venda do plano.
        // No cadastro começa vazio: o vendedor é sempre escolhido.
        user_id: patient?.user_id ?? null,
        ...(isEdit ? { status: Boolean(patient.status) } : {}),
        enderecos,
        cod_plano: '',
        familiares: familiares.map((f) => ({ ...f })),
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

    const saida = useSairSemSalvar(form, { ignorar: enviando })

    return { form, salvar, saida }
}
