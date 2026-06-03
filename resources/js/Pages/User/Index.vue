<script setup>
import { ref, computed, watch } from "vue";
import { Head, router } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import TenantAdminLayout from "@/Layouts/TenantAdminLayout.vue";
import PaginationSimple from "@/Components/PaginationSimple.vue";
import ConfirmDeleteModal from "@/Components/ConfirmDeleteModal.vue";
import { showToast } from "@/Utils/toast";
import {
    Search,
    X,
    Plus,
    Pencil,
    Trash2,
    Mail,
    Calendar,
    User,
    Shield,
} from "lucide-vue-next";

const props = defineProps({
    users: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ search: "" }),
    },
    tenantName: {
        type: String,
        default: "",
    },
    tenantPhoto: {
        type: String,
        default: null,
    },
});

const search = ref(props.filters?.search || "");

const deleteModal = ref({
    show: false,
    user: null,
    isProcessing: false,
});

const userList = computed(() => props.users?.data || []);
const hasUsers = computed(() => userList.value.length > 0);
const hasSearch = computed(() => search.value.length > 0);

let searchTimer = null;

const performSearch = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(
            route("users.index"),
            { search: search.value },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    }, 300);
};

watch(search, performSearch);

const clearSearch = () => {
    search.value = "";
    performSearch();
};

const openEditDialog = (user) => {
    router.visit(route("users.edit", user.id));
};

const confirmDelete = (user) => {
    deleteModal.value = { show: true, user, isProcessing: false };
};

const cancelDelete = () => {
    deleteModal.value.show = false;
};

const executeDelete = () => {
    deleteModal.value.isProcessing = true;
    router.delete(route("users.destroy", deleteModal.value.user.id), {
        onSuccess: () => {
            showToast("Usuário excluído com sucesso.", "success");
            deleteModal.value.show = false;
            deleteModal.value.isProcessing = false;
        },
        onError: () => {
            showToast("Erro ao excluir usuário. Tente novamente.", "error");
            deleteModal.value.isProcessing = false;
        },
    });
};

const getInitials = (name) => {
    if (!name) return "U";
    return name
        .split(" ")
        .map((w) => w[0])
        .join("")
        .substring(0, 2)
        .toUpperCase();
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString("pt-BR", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
    });
};
</script>

<template>

    <Head title="Usuários" />

    <TenantAdminLayout :tenant-name="tenantName" :tenant-photo="tenantPhoto">
        <div class="space-y-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold text-gray-900">Usuários</h1>
                <p class="text-sm text-gray-500">
                    Gerencie os usuários com acesso a este sistema.
                </p>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2 flex-1 min-w-0">
                    <div class="relative flex-1 max-w-sm">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <input v-model="search" type="text" placeholder="Buscar por nome, email ou perfil..."
                            class="block w-full border border-gray-300 rounded-lg py-2 pl-10 pr-10 text-sm bg-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-shadow" />
                        <button v-if="hasSearch" type="button"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                            @click="clearSearch">
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Button size="sm" @click="router.visit(route('users.create'))">
                        <Plus class="w-4 h-4 mr-1" />
                        Adicionar
                    </Button>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow border border-gray-100">
                <div v-if="!hasUsers" class="py-16 text-center">
                    <User class="mx-auto h-10 w-10 text-gray-300" />
                    <h3 class="mt-4 text-lg font-semibold text-gray-800">
                        Nenhum usuário encontrado
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">
                        {{
                            hasSearch
                                ? `Nenhum resultado para "${search}".`
                                : "Nenhum usuário cadastrado ainda."
                        }}
                    </p>
                </div>

                <template v-else>
                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            <div v-for="user in userList" :key="user.id"
                                class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:shadow-md transition-shadow">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div
                                            class="h-10 w-10 shrink-0 rounded-full bg-cyan-600 text-white flex items-center justify-center font-bold text-sm">
                                            {{ getInitials(user.name) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-medium text-gray-900 truncate text-sm">
                                                {{ user.name }}
                                            </p>
                                            <p class="text-xs text-gray-400">ID #{{ user.id }}</p>
                                        </div>
                                    </div>
                                    <div class="flex gap-1 shrink-0">
                                        <button type="button"
                                            class="p-1.5 text-gray-400 hover:text-cyan-600 hover:bg-cyan-50 rounded-lg transition-colors"
                                            title="Editar" @click="openEditDialog(user)">
                                            <Pencil class="w-4 h-4" />
                                        </button>
                                        <button type="button"
                                            class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Excluir" @click="confirmDelete(user)">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-4 space-y-2 text-sm text-gray-500">
                                    <div class="flex items-center gap-2 truncate">
                                        <Mail class="w-4 h-4 shrink-0" />
                                        <span class="truncate">{{ user.email }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Shield class="w-4 h-4 shrink-0 text-cyan-600" />
                                        <span class="font-medium text-cyan-700">{{ user.roles?.[0]?.name ?? '-' }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Calendar class="w-4 h-4 shrink-0" />
                                        {{ formatDate(user.created_at) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <PaginationSimple :data="users" :links="users.links || []" :has-data="hasUsers" label="usuários" />
            </div>
        </div>
    </TenantAdminLayout>

    <ConfirmDeleteModal :show="deleteModal.show" title="Excluir Usuário"
        :message="`Tem certeza que deseja excluir o usuário ${deleteModal.user?.name || ''}?`"
        :item-name="deleteModal.user?.name || ''" confirm-text="Sim, Excluir" cancel-text="Cancelar"
        :is-processing="deleteModal.isProcessing" @confirm="executeDelete" @close="cancelDelete" />
</template>
