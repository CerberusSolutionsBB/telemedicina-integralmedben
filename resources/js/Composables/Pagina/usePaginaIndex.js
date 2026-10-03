import { computed, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { showToast } from '@/Utils/toast'
import { firstError } from './helpers'

/**
 * Listagem de páginas: busca, exclusão, alteração de status e desativação em massa.
 */
export function usePaginaIndex(props) {
    const page = usePage()
    const auth = computed(() => page.props.authUser)
    const can = computed(() => page.props.authUser?.can?.paginas || {})
    const canManage = computed(() => page.props.authUser?.can?.manage || false)
    const flashMessage = computed(() => page.props.flash?.message)
    const flashType = computed(() => page.props.flash?.type)

    watch([flashMessage, flashType], ([message, type]) => {
        if (message) {
            showToast(message, type || 'success')
        }
    })

    const search = ref(props.filters.search || '')
    const planoFilter = ref(props.filters.plano || '')
    const searchInput = ref(null)
    let searchTimer = null

    const deleteModal = ref({ show: false, tenant: null, isProcessing: false })
    const statusModal = ref({ show: false, tenant: null, isProcessing: false })
    const bulkDisableModal = ref({ show: false, isProcessing: false })

    const tenantList = computed(() => props.tenants?.data || [])
    const paginationLinks = computed(() =>
        (props.tenants?.links || []).map(link => ({
            ...link,
            label: link.label.replace('&laquo;', '‹').replace('&raquo;', '›'),
        })),
    )
    const hasTenants = computed(() => tenantList.value.length > 0)
    const hasSearch = computed(() => search.value.length > 0)
    const hasActiveFilters = computed(() => hasSearch.value || planoFilter.value !== '')

    const performSearch = () => {
        clearTimeout(searchTimer)
        searchTimer = setTimeout(() => {
            const params = {}
            if (search.value.trim()) params.search = search.value.trim()
            if (planoFilter.value) params.plano = planoFilter.value
            router.get(route('pagina.index'), params, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['tenants', 'filters'],
            })
        }, 300)
    }

    watch([search, planoFilter], () => performSearch())

    const clearSearch = () => {
        search.value = ''
        planoFilter.value = ''
        searchInput.value?.focus()
        performSearch()
    }

    const openDeleteModal = (item) => {
        if (!can.value.delete) {
            router.visit(route('unauthorized'))
            return
        }
        deleteModal.value = { show: true, tenant: item, isProcessing: false }
    }

    const closeDeleteModal = () => {
        deleteModal.value.show = false
        setTimeout(() => {
            deleteModal.value.tenant = null
            deleteModal.value.isProcessing = false
        }, 200)
    }

    const confirmDelete = () => {
        if (!deleteModal.value.tenant) return
        deleteModal.value.isProcessing = true
        router.delete(route('pagina.destroy', deleteModal.value.tenant.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeDeleteModal()
                showToast('Página excluída com sucesso!', 'success')
            },
            onError: (errors) => {
                showToast(firstError(errors, 'Erro ao excluir página'), 'error')
            },
            onFinish: () => {
                deleteModal.value.isProcessing = false
            },
        })
    }

    const openStatusModal = (item) => {
        statusModal.value = { show: true, tenant: item, isProcessing: false }
    }

    const closeStatusModal = () => {
        statusModal.value.show = false
        setTimeout(() => {
            statusModal.value.tenant = null
            statusModal.value.isProcessing = false
        }, 200)
    }

    const confirmToggleStatus = () => {
        if (!statusModal.value.tenant) return
        statusModal.value.isProcessing = true
        router.put(route('pagina.status', statusModal.value.tenant.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                closeStatusModal()
                showToast('Status atualizado com sucesso!', 'success')
            },
            onError: () => {
                showToast('Erro ao alterar status.', 'error')
            },
            onFinish: () => {
                statusModal.value.isProcessing = false
            },
        })
    }

    const openBulkDisableModal = () => {
        bulkDisableModal.value = { show: true, isProcessing: false }
    }

    const closeBulkDisableModal = () => {
        bulkDisableModal.value.show = false
    }

    const confirmBulkDisable = () => {
        bulkDisableModal.value.isProcessing = true
        router.put(route('pagina.bulk.disable'), {}, {
            preserveScroll: true,
            onSuccess: () => {
                closeBulkDisableModal()
                showToast('Todas as páginas foram desativadas com sucesso!', 'success')
            },
            onError: (errors) => {
                showToast('Erro ao desativar páginas. Tente novamente.', 'error')
                console.error('bulkDisable error:', errors)
            },
            onFinish: () => {
                bulkDisableModal.value.isProcessing = false
            },
        })
    }

    const getTenantDomain = (domains) => {
        if (!domains || !domains.length) return 'Nenhum domínio'
        return domains[0]?.domain || domains[0] || 'Nenhum domínio'
    }

    const getTenantStatus = (item) => (item.status ? 'ativo' : 'inativo')

    const getStatusClass = (status) => {
        const classes = {
            ativo: 'bg-green-100 text-green-800 border-green-200',
            inativo: 'bg-red-100 text-red-800 border-red-200',
            pendente: 'bg-yellow-100 text-yellow-800 border-yellow-200',
            suspenso: 'bg-orange-100 text-orange-800 border-orange-200',
        }
        return classes[status] || 'bg-gray-100 text-gray-800 border-gray-200'
    }

    const getStatusLabel = (status) => {
        const labels = {
            ativo: 'Ativo',
            inativo: 'Inativo',
            pendente: 'Pendente',
            suspenso: 'Suspenso',
        }
        return labels[status] || status
    }

    const getInitials = (name) => {
        if (!name) return 'T'
        return name.charAt(0).toUpperCase()
    }

    const navigateTo = (routeName, params = {}) => {
        router.visit(route(routeName, params))
    }

    return {
        auth,
        can,
        canManage,
        flashMessage,
        flashType,
        search,
        planoFilter,
        searchInput,
        deleteModal,
        statusModal,
        bulkDisableModal,
        tenantList,
        paginationLinks,
        hasTenants,
        hasSearch,
        hasActiveFilters,
        clearSearch,
        openDeleteModal,
        closeDeleteModal,
        confirmDelete,
        openStatusModal,
        closeStatusModal,
        confirmToggleStatus,
        openBulkDisableModal,
        closeBulkDisableModal,
        confirmBulkDisable,
        getTenantDomain,
        getTenantStatus,
        getStatusClass,
        getStatusLabel,
        getInitials,
        navigateTo,
    }
}
