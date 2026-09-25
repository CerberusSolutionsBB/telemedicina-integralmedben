<script setup>
import { ref, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { KeyRound, Pencil, Plus, Search, ShieldCheck, Trash2, Users, X } from "lucide-vue-next";
import { Button } from "@/Components/ui/button";
import PaginationSimple from "@/Components/PaginationSimple.vue";
import AclLayout from "../Partials/AclLayout.vue";
import ConfirmDialog from "../Partials/ConfirmDialog.vue";
import { deleteFeedback, useAcl } from "../Partials/useAcl";

const props = defineProps({
    users: { type: Object, required: true },
    roles: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ search: "", role: "" }) },
    currentUserId: { type: [Number, String], default: null },
});

const { aclRoute, can } = useAcl();

const search = ref(props.filters.search || "");
const role = ref(props.filters.role || "");
let searchTimeout = null;

const applyFilters = () => {
    router.get(
        aclRoute("users.index"),
        { search: search.value || undefined, role: role.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 350);
});
watch(role, applyFilters);

const clearFilters = () => {
    clearTimeout(searchTimeout);
    search.value = "";
    role.value = "";
};

const initials = (name) =>
    (name || "?").split(" ").filter(Boolean).slice(0, 2).map((p) => p[0]).join("").toUpperCase();

const userToDelete = ref(null);
const confirmOpen = ref(false);
const askDelete = (user) => {
    userToDelete.value = user;
    confirmOpen.value = true;
};
const deleteUser = () => {
    router.delete(aclRoute("users.destroy", userToDelete.value.id), deleteFeedback());
};
</script>

<template>
    <AclLayout title="Usuários" :breadcrumbs="[{ label: 'Usuários' }]" description="Gerencie quem acessa o sistema e quais perfis cada pessoa possui.">
        <template #actions>
            <Link v-if="can('users.create')" :href="aclRoute('users.create')">
                <Button><Plus /> Novo usuário</Button>
            </Link>
        </template>

        <!-- Filtros -->
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                <input v-model="search" type="search" placeholder="Buscar por nome ou e-mail..." aria-label="Buscar usuários"
                    class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-9 pr-3 text-sm shadow-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/30" />
            </div>
            <select v-model="role" aria-label="Filtrar por perfil"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/30 sm:w-52">
                <option value="">Todos os perfis</option>
                <option v-for="r in roles" :key="r.id" :value="r.name">{{ r.name }}</option>
            </select>
            <Button v-if="search || role" variant="ghost" @click="clearFilters"><X /> Limpar</Button>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <ul v-if="users.data?.length" class="divide-y divide-gray-100">
                <li v-for="user in users.data" :key="user.id"
                    class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-cyan-100 text-sm font-semibold text-cyan-800">
                            {{ initials(user.name) }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900">
                                {{ user.name }}
                                <span v-if="user.id === currentUserId" class="ml-1 rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-500">você</span>
                            </p>
                            <p class="truncate text-sm text-gray-500">{{ user.email }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-1.5 sm:w-72">
                        <span v-for="r in user.roles" :key="r.id"
                            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="r.name === 'Admin' ? 'bg-cyan-100 text-cyan-800' : 'bg-gray-100 text-gray-700'">
                            <ShieldCheck v-if="r.name === 'Admin'" class="h-3 w-3" /> {{ r.name }}
                        </span>
                        <span v-if="!user.roles.length" class="text-xs italic text-gray-400">Sem perfil</span>
                        <span v-if="user.permissions_count" class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700"
                            :title="`${user.permissions_count} permissão(ões) adicional(is)`">
                            <KeyRound class="h-3 w-3" /> +{{ user.permissions_count }}
                        </span>
                    </div>

                    <div class="flex gap-2 sm:justify-end">
                        <Link v-if="can('users.edit')" :href="aclRoute('users.edit', user.id)">
                            <Button variant="outline" size="sm"><Pencil /> Editar</Button>
                        </Link>
                        <Button v-if="can('users.delete') && user.id !== currentUserId" variant="ghost" size="icon-sm"
                            class="text-red-600 hover:bg-red-50" :aria-label="`Excluir ${user.name}`" @click="askDelete(user)">
                            <Trash2 />
                        </Button>
                    </div>
                </li>
            </ul>

            <div v-else class="px-6 py-12 text-center">
                <Users class="mx-auto h-10 w-10 text-gray-300" />
                <p class="mt-3 text-sm font-medium text-gray-700">
                    {{ search || role ? "Nenhum usuário encontrado com esses filtros." : "Nenhum usuário cadastrado." }}
                </p>
            </div>

            <PaginationSimple v-if="users.data?.length" :data="users" :links="users.links || []" :has-data="true" label="usuários" />
        </div>

        <ConfirmDialog v-model:open="confirmOpen" title="Excluir usuário?"
            :description="`O usuário ${userToDelete?.name ?? ''} perderá o acesso imediatamente. Essa ação não pode ser desfeita.`"
            @confirm="deleteUser" />
    </AclLayout>
</template>
