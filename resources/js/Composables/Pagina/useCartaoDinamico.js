import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { showToast } from '@/Utils/toast'
import { csrfToken } from './helpers'
import { useTenantToggle } from './usePaginaConfig'

export const fontesCartaoDinamico = [
    { value: 'sans-serif', label: 'Sem serifa (padrão)' },
    { value: 'serif', label: 'Com serifa' },
    { value: 'monospace', label: 'Monoespaçada' },
]

const cartaoDinamicoConfigurations = [
    {
        key: 'estilo',
        label: 'Estilo do Cartão',
        description: 'Cores de fundo (gradiente usado quando não houver imagem de frente/verso), cor do texto e fonte.',
        category: 'Aparência',
        type: 'style',
    },
    {
        key: 'logo',
        label: 'Logo',
        description: 'Logo exibida na frente e no verso do cartão.',
        category: 'Imagens',
        type: 'image',
    },
    {
        key: 'frente',
        label: 'Imagem de Frente',
        description: 'Fundo da frente do cartão.',
        category: 'Imagens',
        type: 'image',
    },
    {
        key: 'verso',
        label: 'Imagem de Verso',
        description: 'Fundo do verso do cartão.',
        category: 'Imagens',
        type: 'image',
    },
    {
        key: 'status',
        label: 'Cartão Dinâmico Ativo',
        description: 'Quando ativado, o botão "Cartão Dinâmico" fica disponível na listagem de pacientes deste tenant.',
        category: 'Status',
        type: 'toggle',
    },
    {
        key: 'qrcode',
        label: 'QR Code e Textos do Verso',
        description: 'Habilite o QR Code exibido no verso do cartão, informe os dados que serão codificados e edite os textos fixos do verso.',
        category: 'QR Code',
        type: 'qrcode',
    },
]

const cartaoDinamicoAllCategories = [
    'all',
    ...new Set(cartaoDinamicoConfigurations.map((config) => config.category)),
]

// Os textos destacados vêm de metadados fixos definidos acima,
// então usar v-html com o resultado é seguro.
const escapeHtml = (value) =>
    String(value).replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    }[char]))

/**
 * Aba do Cartão Dinâmico: status, estilo, imagens, QR Code e a busca/filtro das opções.
 */
export function useCartaoDinamico(props) {
    const { isToggling: isTogglingCartaoDinamico, toggle: toggleCartaoDinamico } =
        useTenantToggle(props, 'pagina.configuracao.cartao-dinamico.toggle', 'cartaoDinamicoEnabled')

    // Estilo
    const isSavingCoresCartaoDinamico = ref(false)
    const corPrimariaCartaoDinamico = ref(props.cartaoDinamico?.cor_primaria || '#22d3ee')
    const corSecundariaCartaoDinamico = ref(props.cartaoDinamico?.cor_secundaria || '#0e7490')
    const corTextoCartaoDinamico = ref(props.cartaoDinamico?.cor_texto || '#ffffff')
    const fonteCartaoDinamico = ref(props.cartaoDinamico?.fonte || 'sans-serif')

    // Baseline usada para detectar alterações de estilo ainda não salvas.
    const cartaoDinamicoEstiloSalvo = ref({
        cor_primaria: corPrimariaCartaoDinamico.value,
        cor_secundaria: corSecundariaCartaoDinamico.value,
        cor_texto: corTextoCartaoDinamico.value,
        fonte: fonteCartaoDinamico.value,
    })

    // QR Code e textos do verso
    const isSavingQrcodeCartaoDinamico = ref(false)
    const qrcodeHabilitadoCartaoDinamico = ref(!!props.cartaoDinamico?.qrcode_habilitado)
    const qrcodeDadosCartaoDinamico = ref(props.cartaoDinamico?.qrcode_dados || '')
    const versoTextoInfoCartaoDinamico = ref(props.cartaoDinamico?.verso_texto_info || '')
    const versoRodapeCartaoDinamico = ref(props.cartaoDinamico?.verso_rodape || '')

    // Baseline usada para detectar alterações do QR Code/textos do verso ainda não salvas.
    const cartaoDinamicoQrcodeSalvo = ref({
        habilitado: qrcodeHabilitadoCartaoDinamico.value,
        dados: qrcodeDadosCartaoDinamico.value,
        texto_info: versoTextoInfoCartaoDinamico.value,
        rodape: versoRodapeCartaoDinamico.value,
    })

    // Imagens
    const cartaoDinamicoAssets = ref({
        logo: props.cartaoDinamico?.logo?.url ?? null,
        frente: props.cartaoDinamico?.frente?.url ?? null,
        verso: props.cartaoDinamico?.verso?.url ?? null,
    })
    const isUploadingCartaoImagem = ref({ logo: false, frente: false, verso: false })

    const salvarCoresCartaoDinamico = () => {
        if (!props.tenant?.id) {
            showToast('Tenant não encontrado.', 'error')
            return
        }

        isSavingCoresCartaoDinamico.value = true

        router.put(
            route('pagina.configuracao.cartao-dinamico.cores', props.tenant.id),
            {
                cartao_cor_primaria: corPrimariaCartaoDinamico.value,
                cartao_cor_secundaria: corSecundariaCartaoDinamico.value,
                cartao_cor_texto: corTextoCartaoDinamico.value,
                cartao_fonte: fonteCartaoDinamico.value,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    showToast('Estilo do Cartão Dinâmico salvo com sucesso!', 'success')
                    cartaoDinamicoEstiloSalvo.value = {
                        cor_primaria: corPrimariaCartaoDinamico.value,
                        cor_secundaria: corSecundariaCartaoDinamico.value,
                        cor_texto: corTextoCartaoDinamico.value,
                        fonte: fonteCartaoDinamico.value,
                    }
                },
                onError: () => {
                    showToast('Erro ao salvar o estilo.', 'error')
                },
                onFinish: () => {
                    isSavingCoresCartaoDinamico.value = false
                },
            },
        )
    }

    const salvarQrcodeCartaoDinamico = () => {
        if (!props.tenant?.id) {
            showToast('Tenant não encontrado.', 'error')
            return
        }

        isSavingQrcodeCartaoDinamico.value = true

        router.put(
            route('pagina.configuracao.cartao-dinamico.qrcode', props.tenant.id),
            {
                cartao_qrcode_habilitado: qrcodeHabilitadoCartaoDinamico.value,
                cartao_qrcode_dados: qrcodeDadosCartaoDinamico.value,
                cartao_verso_texto_info: versoTextoInfoCartaoDinamico.value,
                cartao_verso_rodape: versoRodapeCartaoDinamico.value,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    showToast('QR Code e textos do verso salvos com sucesso!', 'success')
                    cartaoDinamicoQrcodeSalvo.value = {
                        habilitado: qrcodeHabilitadoCartaoDinamico.value,
                        dados: qrcodeDadosCartaoDinamico.value,
                        texto_info: versoTextoInfoCartaoDinamico.value,
                        rodape: versoRodapeCartaoDinamico.value,
                    }
                },
                onError: () => {
                    showToast('Erro ao salvar o QR Code e textos do verso.', 'error')
                },
                onFinish: () => {
                    isSavingQrcodeCartaoDinamico.value = false
                },
            },
        )
    }

    async function uploadCartaoImagem(tipo, file) {
        if (!props.tenant?.id) {
            showToast('Tenant não encontrado.', 'error')
            return
        }

        isUploadingCartaoImagem.value[tipo] = true

        try {
            const formData = new FormData()
            formData.append('imagem', file)

            const response = await fetch(route('pagina.configuracao.cartao-dinamico.imagem.store', [props.tenant.id, tipo]), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: formData,
            })

            const data = await response.json()

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Erro ao enviar imagem.')
            }

            cartaoDinamicoAssets.value[tipo] = data.imagem.url
            showToast('Imagem enviada com sucesso!', 'success')
        } catch (error) {
            showToast(error.message || 'Erro ao enviar imagem. Tente novamente.', 'error')
        } finally {
            isUploadingCartaoImagem.value[tipo] = false
        }
    }

    async function deleteCartaoImagem(tipo) {
        if (!props.tenant?.id) {
            showToast('Tenant não encontrado.', 'error')
            return
        }

        isUploadingCartaoImagem.value[tipo] = true

        try {
            const response = await fetch(route('pagina.configuracao.cartao-dinamico.imagem.destroy', [props.tenant.id, tipo]), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            })

            const data = await response.json()

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Erro ao remover imagem.')
            }

            cartaoDinamicoAssets.value[tipo] = null
            showToast('Imagem removida com sucesso!', 'success')
        } catch (error) {
            showToast(error.message || 'Erro ao remover imagem. Tente novamente.', 'error')
        } finally {
            isUploadingCartaoImagem.value[tipo] = false
        }
    }

    const handleCartaoImagemChange = (tipo, value) => {
        if (value instanceof File) {
            uploadCartaoImagem(tipo, value)
        } else if (value === null) {
            deleteCartaoImagem(tipo)
        }
    }

    // Opções de configuração (busca + edição)
    const cartaoDinamicoSearch = ref('')
    const cartaoDinamicoActiveCategory = ref('all')
    // Apenas um card aberto por vez (accordion) e nada expandido por padrão:
    // reduz a quantidade de informação simultânea na tela.
    const cartaoDinamicoExpandedKey = ref(null)

    const cartaoDinamicoFilteredConfigurations = computed(() => {
        let configs = cartaoDinamicoConfigurations

        if (cartaoDinamicoActiveCategory.value !== 'all') {
            configs = configs.filter((config) => config.category === cartaoDinamicoActiveCategory.value)
        }

        const term = cartaoDinamicoSearch.value.toLowerCase().trim()

        if (!term) {
            return configs
        }

        return configs.filter(
            (config) =>
                config.label.toLowerCase().includes(term) ||
                config.description.toLowerCase().includes(term) ||
                config.key.toLowerCase().includes(term),
        )
    })

    const cartaoDinamicoConfigsByCategory = computed(() => {
        const grouped = {}

        cartaoDinamicoFilteredConfigurations.value.forEach((config) => {
            if (!grouped[config.category]) {
                grouped[config.category] = []
            }

            grouped[config.category].push(config)
        })

        return grouped
    })

    const isCartaoDinamicoExpanded = (key) =>
        cartaoDinamicoExpandedKey.value === key || cartaoDinamicoSearch.value.trim().length > 0

    const toggleCartaoDinamicoExpand = (key) => {
        cartaoDinamicoExpandedKey.value = cartaoDinamicoExpandedKey.value === key ? null : key
    }

    const clearCartaoDinamicoFiltros = () => {
        cartaoDinamicoSearch.value = ''
        cartaoDinamicoActiveCategory.value = 'all'
    }

    const cartaoDinamicoTypeIconClasses = (config) => {
        if (config.type === 'toggle') {
            return props.cartaoDinamicoEnabled
                ? 'bg-emerald-50 text-emerald-600'
                : 'bg-gray-100 text-gray-400'
        }

        if (config.type === 'image') {
            return 'bg-cyan-50 text-cyan-600'
        }

        if (config.type === 'qrcode') {
            return qrcodeHabilitadoCartaoDinamico.value
                ? 'bg-indigo-50 text-indigo-600'
                : 'bg-gray-100 text-gray-400'
        }

        return 'bg-purple-50 text-purple-600'
    }

    // Destaca o termo pesquisado no rótulo/descrição para reduzir o esforço de
    // leitura ao escanear os resultados.
    const cartaoDinamicoHighlight = (text) => {
        const safe = escapeHtml(text || '')
        const term = cartaoDinamicoSearch.value.trim()

        if (!term) return safe

        const escapedTerm = escapeHtml(term).replace(/[.*+?^${}()|[\]\\]/g, '\\$&')

        return safe.replace(
            new RegExp(`(${escapedTerm})`, 'ig'),
            '<mark class="bg-amber-200 text-gray-900 rounded px-0.5">$1</mark>',
        )
    }

    // Indicador de "alterações não salvas" (cards com edição em duas etapas:
    // ajustar e depois salvar).
    const cartaoDinamicoHasUnsavedChanges = (config) => {
        if (config.type === 'style') {
            return (
                corPrimariaCartaoDinamico.value !== cartaoDinamicoEstiloSalvo.value.cor_primaria ||
                corSecundariaCartaoDinamico.value !== cartaoDinamicoEstiloSalvo.value.cor_secundaria ||
                corTextoCartaoDinamico.value !== cartaoDinamicoEstiloSalvo.value.cor_texto ||
                fonteCartaoDinamico.value !== cartaoDinamicoEstiloSalvo.value.fonte
            )
        }

        if (config.type === 'qrcode') {
            return (
                qrcodeHabilitadoCartaoDinamico.value !== cartaoDinamicoQrcodeSalvo.value.habilitado ||
                qrcodeDadosCartaoDinamico.value !== cartaoDinamicoQrcodeSalvo.value.dados ||
                versoTextoInfoCartaoDinamico.value !== cartaoDinamicoQrcodeSalvo.value.texto_info ||
                versoRodapeCartaoDinamico.value !== cartaoDinamicoQrcodeSalvo.value.rodape
            )
        }

        return false
    }

    return {
        fontesCartaoDinamico,
        isTogglingCartaoDinamico,
        toggleCartaoDinamico,
        isSavingCoresCartaoDinamico,
        corPrimariaCartaoDinamico,
        corSecundariaCartaoDinamico,
        corTextoCartaoDinamico,
        fonteCartaoDinamico,
        salvarCoresCartaoDinamico,
        isSavingQrcodeCartaoDinamico,
        qrcodeHabilitadoCartaoDinamico,
        qrcodeDadosCartaoDinamico,
        versoTextoInfoCartaoDinamico,
        versoRodapeCartaoDinamico,
        salvarQrcodeCartaoDinamico,
        cartaoDinamicoAssets,
        isUploadingCartaoImagem,
        handleCartaoImagemChange,
        cartaoDinamicoSearch,
        cartaoDinamicoActiveCategory,
        cartaoDinamicoExpandedKey,
        cartaoDinamicoConfigurations,
        cartaoDinamicoAllCategories,
        cartaoDinamicoFilteredConfigurations,
        cartaoDinamicoConfigsByCategory,
        isCartaoDinamicoExpanded,
        toggleCartaoDinamicoExpand,
        clearCartaoDinamicoFiltros,
        cartaoDinamicoTypeIconClasses,
        cartaoDinamicoHighlight,
        cartaoDinamicoHasUnsavedChanges,
    }
}
