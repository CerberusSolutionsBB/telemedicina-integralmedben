<script setup>
import CentralAdminLayout from '@/Layouts/CentralAdminLayout.vue'
import Breadcrumb from '@/Components/Breadcrumb.vue'
import PaginationSimple from '@/Components/PaginationSimple.vue'
import ActionDropdown from '@/Components/ActionDropdown.vue'
import ConfirmDeleteModal from '@/Components/ConfirmDeleteModal.vue'
import Button from '@/Components/ui/button/Button.vue'
import { router } from '@inertiajs/vue3'
import { showToast } from '@/Utils/toast'

import {
    Search,
    X,
    User,
    Mail,
    Calendar,
    Home,
    Shield,
    KeyRound,
    BadgeCheck,
    Pencil,
    Trash2,
    Plus,
} from 'lucide-vue-next'

import { ref, computed, watch } from 'vue'

const props = defineProps({
    users: {
        type: Object,
        default: () => ({
            data: [],
            links: [],
            from: 0,
            to: 0,
            total: 0,
        }),
    },
    tenant: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ search: '' }),
    },
})

const search = ref(props.filters?.search || '')
let searchTimer = null

const deleteModal = ref({
    show: false,
    user: null,
    isProcessing: false,
})

const userList = computed(() => props.users?.data || [])
const hasUsers = computed(() => userList.value.length > 0)
const hasSearch = computed(() => search.value.length > 0)

const breadcrumbs = computed(() => [
    {
        label: 'Páginas',
        href: route('pagina.index'),
        icon: Home,
    },
    // {
    //     label: props.tenant?.name || 'Tenant',
    // },
    {
        label: 'Usuários',
    },
])

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
            }
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
    router.visit(
        route('pagina.users.edit', {
            tenant: props.tenant.id,
            user: user.id,
        })
    )
}

const openDeleteModal = (user) => {
    deleteModal.value = {
        show: true,
        user,
        isProcessing: false,
    }
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
        route('pagina.users.destroy', {
            tenant: props.tenant.id,
            user: deleteModal.value.user.id,
        }),
        {
            preserveScroll: true,

            onSuccess: () => {
                closeDeleteModal()
                showToast('Usuário removido com sucesso!', 'success')
            },

            onError: (errors) => {
                const message =
                    Object.values(errors).flat()[0] ||
                    'Erro ao remover usuário'

                showToast(message, 'error')
            },

            onFinish: () => {
                deleteModal.value.isProcessing = false
            },
        }
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

const groupPermissions = (permissions = []) => {
    return permissions.reduce((groups, permission) => {
        const [module, ...actions] = String(permission).split('.')
        const action = actions.join('.') || permission

        if (!groups[module]) {
            groups[module] = []
        }

        groups[module].push({
            full: permission,
            action,
        })

        return groups
    }, {})
}

const moduleLabel = (module) => {
    const labels = {
        users: 'Usuários',
        forms: 'Formulários',
        paginas: 'Páginas',
        roles: 'Roles',
        permissions: 'Permissões',
    }

    return labels[module] || module
}

const permissionActionLabel = (action) => {
    const labels = {
        view: 'Visualizar',
        create: 'Criar',
        edit: 'Editar',
        delete: 'Excluir',
        manage: 'Gerenciar',
        'update.status': 'Atualizar status',
        'toggle.visibility': 'Visibilidade',
        'manage.all': 'Gerenciar tudo',
    }

    return labels[action] || action
}
</script>

<template>
    <CentralAdminLayout>
        <div class="space-y-6">
            <Breadcrumb :items="breadcrumbs" />

            <div class="bg-white rounded-[2rem] p-5 md:p-8 border border-white shadow-sm">
                <div class="flex flex-col gap-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold text-slate-800">
                                Usuários do {{ props.tenant?.name }}
                            </h1>

                            <p class="text-sm text-slate-500 mt-2">
                                Gerencie permissões e acessos dos usuários deste tenant.
                            </p>
                        </div>

                        <Button type="button" class="rounded-2xl bg-primary hover:bg-primary-hover text-white border-0"
                            @click="createUser">
                            <Plus class="w-4 h-4" />
                            Novo usuário
                        </Button>
                    </div>

                    <div class="rounded-3xl bg-white p-4 shadow-sm border border-slate-100">
                        <div class="relative">
                            <Search class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />

                            <input v-model="search" type="text" placeholder="Buscar usuário por nome ou email..."
                                class="input input-bordered w-full rounded-2xl pl-12 pr-12 h-14 bg-slate-50 border-slate-100" />

                            <button v-if="hasSearch" type="button" class="absolute right-4 top-1/2 -translate-y-1/2"
                                @click="clearSearch">
                                <X class="h-5 w-5 text-slate-400" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="hasUsers" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <article v-for="user in userList" :key="user.id"
                    class="rounded-3xl bg-white border border-slate-100 p-6 shadow-sm hover:shadow-xl transition">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-4 min-w-0">
                            <div
                                class="h-14 w-14 shrink-0 rounded-2xl bg-primary text-white flex items-center justify-center font-bold text-lg">
                                {{ getInitials(user.name) }}
                            </div>

                            <div class="min-w-0">
                                <h3 class="font-bold text-slate-800 truncate">
                                    {{ user.name }}
                                </h3>

                                <p class="text-xs text-slate-400">
                                    ID #{{ user.id }}
                                </p>
                            </div>
                        </div>

                        <div class="shrink-0">
                            <ActionDropdown>
                                <template #default="{ close }">
                                    <button
                                        class="w-full flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 transition"
                                        @click="
                                            editUser(user);
                                        close();
                                        ">
                                        <Pencil class="w-4 h-4" />
                                        Editar usuário
                                    </button>

                                    <div class="border-t border-slate-100 my-1"></div>

                                    <button
                                        class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition"
                                        @click="
                                            openDeleteModal(user);
                                        close();
                                        ">
                                        <Trash2 class="w-4 h-4" />
                                        Remover usuário
                                    </button>
                                </template>
                            </ActionDropdown>
                        </div>
                    </div>

                    <div class="mt-5 space-y-3">
                        <div class="flex items-center gap-2 text-sm text-slate-500">
                            <Mail class="w-4 h-4" />
                            <span class="truncate">
                                {{ user.email }}
                            </span>
                        </div>

                        <div class="flex items-center gap-2 text-sm text-slate-500">
                            <Calendar class="w-4 h-4" />
                            {{ user.created_at }}
                        </div>
                    </div>

                    <div class="mt-6">
                        <div class="flex items-center gap-2 mb-3">
                            <Shield class="w-4 h-4 text-primary" />

                            <span class="text-sm font-semibold text-slate-700">
                                Roles
                            </span>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span v-for="role in user.roles" :key="role.id ?? role"
                                class="rounded-full bg-primary/10 border border-primary/20 px-3 py-1 text-xs font-medium text-primary">
                                <BadgeCheck class="w-3 h-3 mr-1 inline" />
                                {{ role.name ?? role }}
                            </span>

                            <span v-if="!user.roles?.length" class="text-xs text-slate-400">
                                Sem roles
                            </span>
                        </div>
                    </div>

                    <div class="mt-6">
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <div class="flex items-center gap-2">
                                <KeyRound class="w-4 h-4 text-amber-500" />

                                <span class="text-sm font-semibold text-slate-700">
                                    Permissões
                                </span>
                            </div>

                            <span v-if="user.all_permissions?.length"
                                class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700">
                                {{ user.all_permissions.length }}
                            </span>
                        </div>

                        <div v-if="user.all_permissions?.length" class="space-y-3 max-h-52 overflow-y-auto pr-1">
                            <div v-for="(permissions, module) in groupPermissions(user.all_permissions)" :key="module"
                                class="rounded-2xl border border-slate-100 bg-slate-50 p-3">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="text-xs font-bold uppercase tracking-wide text-slate-500">
                                        {{ moduleLabel(module) }}
                                    </span>

                                    <span class="text-[11px] text-slate-400">
                                        {{ permissions.length }}
                                    </span>
                                </div>

                                <div class="flex flex-wrap gap-1.5">
                                    <span v-for="permission in permissions" :key="permission.full"
                                        :title="permission.full"
                                        class="rounded-full bg-white border border-amber-100 px-2.5 py-1 text-[11px] font-medium text-amber-700">
                                        {{ permissionActionLabel(permission.action) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <span v-else class="text-xs text-slate-400">
                            Sem permissões
                        </span>
                    </div>
                </article>
            </div>

            <div v-else class="rounded-3xl bg-white border border-slate-100 py-16 text-center">
                <User class="mx-auto h-10 w-10 text-slate-300" />

                <h3 class="mt-4 text-lg font-bold text-slate-800">
                    Nenhum usuário encontrado
                </h3>

                <p class="text-sm text-slate-500 mt-1">
                    {{
                        hasSearch
                            ? `Nenhum resultado para "${search}".`
                            : 'Este tenant ainda não possui usuários.'
                    }}
                </p>
            </div>

            <PaginationSimple :data="props.users" :links="props.users?.links || []" :has-data="hasUsers"
                label="usuários" />

            <ConfirmDeleteModal :show="deleteModal.show" :item-name="deleteModal.user?.name" title="Remover usuário"
                message="Tem certeza que deseja remover este usuário?"
                warning-message="Todos os acessos deste usuário serão removidos. Esta ação não poderá ser desfeita."
                confirm-text="Sim, remover" cancel-text="Cancelar" :is-processing="deleteModal.isProcessing"
                variant="danger" @close="closeDeleteModal" @confirm="confirmDelete" />
        </div>
    </CentralAdminLayout>
</template>
