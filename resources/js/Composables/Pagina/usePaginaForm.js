import { computed, ref } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import { showToast } from '@/Utils/toast'
import { firstError } from './helpers'

const inputClass = 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all'
const inputErrorClass = 'w-full px-3 py-2 border border-red-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 bg-red-50 transition-all'
const inputFilledClass = 'w-full px-3 py-2 border border-green-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 bg-green-50/30 transition-all'

/**
 * Formulário de criação/edição de página (tenant + usuário administrador).
 */
export function usePaginaForm(props) {
    const page = usePage()
    const auth = computed(() => page.props.authUser || {})
    const can = computed(() => page.props.authUser?.can?.paginas || {})
    const canManage = computed(() => page.props.authUser?.can?.manage || false)

    const isEdit = computed(() => !!props.tenant)

    const form = useForm({
        descricao: props.tenant?.data?.descricao || '',
        nome: props.tenant?.data?.nome || '',
        email: props.tenant?.data?.email || '',
        senha: '',
        senha_confirmation: '',
        forms: props.tenant?.data?.forms?.map(f => f.id) || [],
    })

    const dialogOpen = ref(false)

    const selectedFormsNames = computed(() =>
        props.forms
            .filter(f => form.forms.includes(f.id))
            .map(f => f.title),
    )

    const paginaSectionFilled = computed(() => {
        const hasDescricao = form.descricao.trim().length > 0
        const hasForms = form.forms.length > 0
        return { hasDescricao, hasForms, total: [hasDescricao, hasForms].filter(Boolean).length, max: 2 }
    })

    const adminSectionFilled = computed(() => {
        const hasNome = form.nome.trim().length > 0
        const hasEmail = form.email.trim().length > 0
        const hasSenha = isEdit.value ? true : form.senha.length > 0
        const hasConfirmacao = isEdit.value ? true : form.senha_confirmation.length > 0
        return {
            hasNome,
            hasEmail,
            hasSenha,
            hasConfirmacao,
            total: [hasNome, hasEmail, hasSenha, hasConfirmacao].filter(Boolean).length,
            max: 4,
        }
    })

    const totalProgress = computed(() => {
        const total = paginaSectionFilled.value.total + adminSectionFilled.value.total
        const max = paginaSectionFilled.value.max + adminSectionFilled.value.max
        return Math.round((total / max) * 100)
    })

    const getInputClass = (fieldName, hasValue) => {
        if (form.errors[fieldName]) return inputErrorClass
        if (hasValue && !form.processing) return inputFilledClass
        return inputClass
    }

    const submit = () => {
        if (isEdit.value) {
            form.put(route('pagina.update', props.tenant.id), {
                preserveScroll: true,
                onSuccess: () => {
                    showToast('Registro atualizado com sucesso!', 'success')
                },
                onError: (errors) => {
                    showToast(firstError(errors, 'Erro ao atualizar'), 'error')
                },
            })
        } else {
            form.post(route('pagina.store'), {
                preserveScroll: true,
                onSuccess: () => {
                    showToast('Registro criado com sucesso!', 'success')
                    form.reset()
                },
                onError: (errors) => {
                    showToast(firstError(errors, 'Erro ao criar'), 'error')
                },
            })
        }
    }

    const goBack = () => {
        router.visit(route('pagina.index'))
    }

    return {
        auth,
        can,
        canManage,
        isEdit,
        inputClass,
        inputErrorClass,
        inputFilledClass,
        form,
        dialogOpen,
        selectedFormsNames,
        paginaSectionFilled,
        adminSectionFilled,
        totalProgress,
        getInputClass,
        submit,
        goBack,
    }
}
