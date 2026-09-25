<script setup>
import { computed, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { KeyRound, Lock, Pencil, Plus, Search, Trash2 } from "lucide-vue-next";
import { Button } from "@/Components/ui/button";
import AclLayout from "../Partials/AclLayout.vue";
import ConfirmDialog from "../Partials/ConfirmDialog.vue";
import PermissionDialog from "../Partials/PermissionDialog.vue";
import { actionLabel, groupPermissions, permissionAction, deleteFeedback, useAcl } from "../Partials/useAcl";

const props = defineProps({
    permissions: { type: Array, required: true },
    filters: { type: Object, default: () => ({ search: "" }) },
});

const { aclRoute, can } = useAcl();

const search = ref(props.filters.search || "");
let searchTimeout = null;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(aclRoute("permissions.index"), { search: search.value || undefined },
            { preserveState: true, preserveScroll: true, replace: true });
    }, 350);
});

const byName = computed(() => Object.fromEntries(props.permissions.map((p) => [p.name, p])));
const groups = computed(() =>
    groupPermissions(props.permissions.map((p) => p.name)).map((group) => ({
        ...group,
        items: group.permissions.map((name) => byName.value[name]),
    }))
);

const dialogOpen = ref(false);
const editing = ref(null);
const openCreate = () => {
    editing.value = null;
    dialogOpen.value = true;
};
const openEdit = (permission) => {
    editing.value = permission;
    dialogOpen.value = true;
};

const toDelete = ref(null);
const confirmOpen = ref(false);
const askDelete = (permission) => {
    toDelete.value = permission;
    confirmOpen.value = true;
};
const deleteDescription = computed(() => {
    const p = toDelete.value;
    if (!p) return "";
    const uso = [];
    if (p.roles_count) uso.push(`${p.roles_count} perfil(is)`);
    if (p.users_count) uso.push(`${p.users_count} usuário(s)`);
    return `A permissão ${p.name} será removida${uso.length ? ` de ${uso.join(" e ")}` : ""}. Telas que dependem dela ficarão inacessíveis.`;
});
const deletePermission = () =>
    router.delete(aclRoute("permissions.destroy", toDelete.value.id), deleteFeedback());
</script>

<template>
    <AclLayout title="Permissões" :breadcrumbs="[{ label: 'Permissões' }]" description="Ações que podem ser liberadas para perfis e usuários, organizadas por módulo.">
        <template #actions>
            <Button v-if="can('permissions.create')" @click="openCreate"><Plus /> Nova permissão</Button>
        </template>

        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative sm:w-80">
                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                <input v-model="search" type="search" placeholder="Buscar permissão..." aria-label="Buscar permissões"
                    class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-9 pr-3 text-sm shadow-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/30" />
            </div>
            <p class="text-sm text-gray-500">{{ permissions.length }} permissões em {{ groups.length }} módulos</p>
        </div>

        <div v-if="groups.length" class="space-y-4">
            <section v-for="group in groups" :key="group.module"
                class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <header class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-4 py-2.5">
                    <h2 class="text-sm font-semibold text-gray-800">{{ group.label }}</h2>
                    <span class="text-xs text-gray-500">{{ group.items.length }}</span>
                </header>
                <ul class="divide-y divide-gray-100">
                    <li v-for="permission in group.items" :key="permission.id"
                        class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center">
                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-1.5 text-sm font-medium text-gray-900">
                                {{ actionLabel(permissionAction(permission.name)) }}
                                <Lock v-if="permission.protected" class="h-3.5 w-3.5 text-gray-400"
                                    aria-label="Permissão do sistema" />
                            </p>
                            <p class="truncate font-mono text-xs text-gray-400">{{ permission.name }}</p>
                        </div>
                        <div class="flex items-center gap-3 text-xs text-gray-500">
                            <span>{{ permission.roles_count }} perfil(is)</span>
                            <span v-if="permission.users_count">{{ permission.users_count }} usuário(s)</span>
                        </div>
                        <div v-if="!permission.protected" class="flex gap-1 sm:w-20 sm:justify-end">
                            <Button v-if="can('permissions.edit')" variant="ghost" size="icon-sm"
                                :aria-label="`Editar ${permission.name}`" @click="openEdit(permission)">
                                <Pencil />
                            </Button>
                            <Button v-if="can('permissions.delete')" variant="ghost" size="icon-sm"
                                class="text-red-600 hover:bg-red-50" :aria-label="`Excluir ${permission.name}`"
                                @click="askDelete(permission)">
                                <Trash2 />
                            </Button>
                        </div>
                        <div v-else class="text-xs text-gray-400 sm:w-20 sm:text-right">Sistema</div>
                    </li>
                </ul>
            </section>
        </div>

        <div v-else class="rounded-lg border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
            <KeyRound class="mx-auto h-10 w-10 text-gray-300" />
            <p class="mt-3 text-sm font-medium text-gray-700">
                {{ search ? "Nenhuma permissão encontrada." : "Nenhuma permissão cadastrada." }}
            </p>
        </div>

        <PermissionDialog v-model:open="dialogOpen" :permission="editing" />
        <ConfirmDialog v-model:open="confirmOpen" title="Excluir permissão?" :description="deleteDescription"
            @confirm="deletePermission" />
    </AclLayout>
</template>
