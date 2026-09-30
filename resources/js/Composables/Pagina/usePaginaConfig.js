import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { showToast } from '@/Utils/toast'

/**
 * Cria uma ação que alterna um recurso do tenant via PUT e recarrega a prop indicada.
 */
export function useTenantToggle(props, routeName, reloadProp) {
    const isToggling = ref(false)

    const toggle = () => {
        if (!props.tenant?.id) {
            showToast('Tenant não encontrado.', 'error')
            return
        }

        isToggling.value = true

        router.put(route(routeName, props.tenant.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                showToast('Status atualizado com sucesso!', 'success')
                router.reload({ only: [reloadProp], preserveScroll: true })
            },
            onError: () => {
                showToast('Erro ao atualizar status.', 'error')
            },
            onFinish: () => {
                isToggling.value = false
            },
        })
    }

    return { isToggling, toggle }
}

/**
 * Aba de configuração: formulário dinâmico e cartão do paciente.
 */
export function usePaginaConfig(props) {
    const { isToggling: isTogglingStatus, toggle: toggleStatusFormularioDinamico } =
        useTenantToggle(props, 'pagina.configuracao.status-formulario-dinamico', 'statusFormularioDinamico')

    const { isToggling: isTogglingCartaoPaciente, toggle: toggleCartaoPaciente } =
        useTenantToggle(props, 'pagina.configuracao.cartao-paciente', 'cartaoPacienteEnabled')

    return {
        isTogglingStatus,
        toggleStatusFormularioDinamico,
        isTogglingCartaoPaciente,
        toggleCartaoPaciente,
    }
}
