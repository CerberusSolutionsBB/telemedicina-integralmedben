import { computed, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { showToast } from '@/Utils/toast'
import { firstError } from './helpers'

const moduleLabels = {
    users: 'Usuários',
    forms: 'Formulários',
    paginas: 'Páginas',
    roles: 'Roles',
    permissions: 'Permissões',
}

const permissionActionLabels = {
    view: 'Visualizar',
    create: 'Criar',
    edit: 'Editar',
    delete: 'Excluir',
    manage: 'Gerenciar',
    'update.status': 'Atualizar status',
    'toggle.visibility': 'Visibilidade',
    'manage.all': 'Gerenciar tudo',
}

/**
 * Listagem de usuários de uma página: busca, navegação e exclusão.
 */
export function usePaginaUsers(props) {
    const search = ref(props.filters?.search || '')
    let searchTimer = null

    const deleteModal = ref({ show: false, user: null, isProcessing: false })

    const userList = computed(() => props.users?.data || [])
    const hasUsers = computed(() => userList.value.length > 0)
    const hasSearch = computed(() => search.value.length > 0)

    const performSearch = () => {
        clearTimeout(searchTimer)

        searchTimer = setTimeout(() => {
            router.get(
                route('pagina.users.index', props.tenant.id),
                { search: search.value },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['users', 'filters'],
                },
            )
        }, 300)
    }

    watch(search, performSearch)

    const clearSearch = () => {
        search.value = ''
        performSearch()
    }

    const createUser = () => {
        router.visit(route('pagina.users.create', props.tenant.id))
    }

    const editUser = (user) => {
        router.visit(route('pagina.users.edit', { tenant: props.tenant.id, user: user.id }))
    }

    const openDeleteModal = (user) => {
        deleteModal.value = { show: true, user, isProcessing: false }
    }

    const closeDeleteModal = () => {
        deleteModal.value.show = false

        setTimeout(() => {
            deleteModal.value.user = null
            deleteModal.value.isProcessing = false
        }, 200)
    }

    const confirmDelete = () => {
        if (!deleteModal.value.user) return

        deleteModal.value.isProcessing = true

        router.delete(
            route('pagina.users.destroy', { tenant: props.tenant.id, user: deleteModal.value.user.id }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    closeDeleteModal()
                    showToast('Usuário removido com sucesso!', 'success')
                },
                onError: (errors) => {
                    showToast(firstError(errors, 'Erro ao remover usuário'), 'error')
                },
                onFinish: () => {
                    deleteModal.value.isProcessing = false
                },
            },
        )
    }

    const getInitials = (name) => {
        if (!name) return 'US'

        return name
            .split(' ')
            .map((word) => word[0])
            .join('')
            .substring(0, 2)
            .toUpperCase()
    }

    const groupPermissions = (permissions = []) =>
        permissions.reduce((groups, permission) => {
            const [module, ...actions] = String(permission).split('.')
            const action = actions.join('.') || permission

            if (!groups[module]) {
                groups[module] = []
            }

            groups[module].push({ full: permission, action })

            return groups
        }, {})

    const moduleLabel = (module) => moduleLabels[module] || module

    const permissionActionLabel = (action) => permissionActionLabels[action] || action

    return {
        search,
        deleteModal,
        userList,
        hasUsers,
        hasSearch,
        clearSearch,
        createUser,
        editUser,
        openDeleteModal,
        closeDeleteModal,
        confirmDelete,
        getInitials,
        groupPermissions,
        moduleLabel,
        permissionActionLabel,
    }
}
