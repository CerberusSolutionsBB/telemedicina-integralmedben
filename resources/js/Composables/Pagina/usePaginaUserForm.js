import { computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { showToast } from '@/Utils/toast'
import { firstError } from './helpers'

const inputClass =
    'input input-bordered w-full rounded-2xl pl-12 h-14 bg-slate-50 border-slate-100 focus:border-primary focus:ring-2 focus:ring-primary/20'

const passwordInputClass =
    'input input-bordered w-full rounded-2xl pl-12 pr-12 h-14 bg-slate-50 border-slate-100 focus:border-primary focus:ring-2 focus:ring-primary/20'

const inputErrorClass =
    'input input-bordered w-full rounded-2xl pl-12 h-14 bg-red-50 border-red-300 focus:border-red-500 focus:ring-2 focus:ring-red-100'

const passwordInputErrorClass =
    'input input-bordered w-full rounded-2xl pl-12 pr-12 h-14 bg-red-50 border-red-300 focus:border-red-500 focus:ring-2 focus:ring-red-100'

/**
 * Formulário de criação/edição de usuário de uma página.
 * Quando `props.user` existe, o formulário opera em modo edição.
 */
export function usePaginaUserForm(props) {
    const isEdit = computed(() => !!props.user)

    const form = useForm({
        name: props.user?.name || '',
        email: props.user?.email || '',
        password: '',
        role: props.selectedRoles?.[0] || '',
    })

    const submit = () => {
        if (isEdit.value) {
            form.put(
                route('pagina.users.update', { tenant: props.tenant.id, user: props.user.id }),
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        form.reset('password')
                        showToast('Usuário atualizado com sucesso!', 'success')
                    },
                    onError: (errors) => {
                        showToast(firstError(errors, 'Erro ao atualizar usuário'), 'error')
                    },
                },
            )
            return
        }

        form.post(route('pagina.users.store', { tenant: props.tenant.id }), {
            preserveScroll: true,
            onSuccess: () => {
                showToast('Usuário criado com sucesso!', 'success')
            },
            onError: (errors) => {
                showToast(firstError(errors, 'Erro ao criar usuário'), 'error')
            },
        })
    }

    const goBack = () => {
        router.visit(route('pagina.users.index', props.tenant.id))
    }

    return {
        isEdit,
        inputClass,
        passwordInputClass,
        inputErrorClass,
        passwordInputErrorClass,
        form,
        submit,
        goBack,
    }
}
