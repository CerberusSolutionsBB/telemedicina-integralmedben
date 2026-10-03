import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

/**
 * Ações do CRUD de beneficiários habilitadas para o parceiro (aba Beneficiário
 * da página do parceiro). Compartilhadas pelo HandleInertiaRequests.
 */
export function usePermissoesBeneficiario() {
    const page = usePage()
    const permissoes = computed(() => page.props.beneficiarioPermissoes ?? {})

    return {
        podeCriar: computed(() => Boolean(permissoes.value.create)),
        podeEditar: computed(() => Boolean(permissoes.value.edit)),
        podeExcluir: computed(() => Boolean(permissoes.value.delete)),
        podeAlterarStatus: computed(() => Boolean(permissoes.value.status)),
    }
}
