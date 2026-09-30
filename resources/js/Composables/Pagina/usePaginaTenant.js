import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { showToast } from '@/Utils/toast'
import { firstError } from './helpers'

/**
 * Dados básicos do tenant exibidos na página de detalhes.
 */
export function usePaginaTenant(props) {
    const detail = computed(() => props.tenant?.details?.[0] ?? null)
    const user = computed(() => detail.value?.user ?? null)
    const domains = computed(() => props.tenant?.domains ?? [])

    const tenantName = computed(() =>
        detail.value?.descricao || detail.value?.slug || props.tenant?.id || 'Tenant',
    )

    const tenantSlug = computed(() => detail.value?.slug || props.tenant?.id || '-')

    const isGeneratingDetail = ref(false)

    const copyToClipboard = async (text) => {
        if (!text) {
            showToast('Link não encontrado.', 'error')
            return
        }

        try {
            await navigator.clipboard.writeText(text)
            showToast('Link copiado com sucesso!', 'success')
        } catch {
            showToast('Não foi possível copiar o link.', 'error')
        }
    }

    const generateDetail = (tenantId) => {
        if (!tenantId) {
            showToast('Identificador da página não encontrado.', 'error')
            return
        }

        isGeneratingDetail.value = true

        router.get(route('pagina.configuracao.generate.detail', tenantId), {}, {
            preserveScroll: true,
            onSuccess: () => {
                showToast('Configuração gerada com sucesso!', 'success')
            },
            onError: (errors) => {
                showToast(firstError(errors, 'Erro ao gerar configuração.'), 'error')
            },
            onFinish: () => {
                isGeneratingDetail.value = false
            },
        })
    }

    return {
        detail,
        user,
        domains,
        tenantName,
        tenantSlug,
        isGeneratingDetail,
        copyToClipboard,
        generateDetail,
    }
}
