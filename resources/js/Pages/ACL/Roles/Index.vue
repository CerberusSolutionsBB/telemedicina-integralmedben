<script setup>
import { ref, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { Eye, Lock, Pencil, Plus, Search, ShieldCheck, Trash2, Users } from "lucide-vue-next";
import { Button } from "@/Components/ui/button";
import AclLayout from "../Partials/AclLayout.vue";
import ConfirmDialog from "../Partials/ConfirmDialog.vue";
import { deleteFeedback, useAcl } from "../Partials/useAcl";

const props = defineProps({
    roles: { type: Array, required: true },
    totalPermissions: { type: Number, default: 0 },
    filters: { type: Object, default: () => ({ search: "" }) },
});

const { aclRoute, can } = useAcl();

const search = ref(props.filters.search || "");
let searchTimeout = null;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(aclRoute("roles.index"), { search: search.value || undefined },
            { preserveState: true, preserveScroll: true, replace: true });
    }, 350);
});

const coverage = (role) =>
    props.totalPermissions ? Math.round((role.permissions_count / props.totalPermissions) * 100) : 0;

const roleToDelete = ref(null);
const confirmOpen = ref(false);
const askDelete = (role) => {
    roleToDelete.value = role;
    confirmOpen.value = true;
};
const deleteRole = () => router.delete(aclRoute("roles.destroy", roleToDelete.value.id), deleteFeedback());
</script>

<template>
    <AclLayout title="Perfis" :breadcrumbs="[{ label: 'Perfis' }]" description="Perfis agrupam permissões. Atribua perfis aos usuários para controlar o que cada um pode fazer.">
        <template #actions>
            <Link v-if="can('roles.create')" :href="aclRoute('roles.create')">
                <Button><Plus /> Novo perfil</Button>
            </Link>
        </template>

        <div class="relative mb-4 sm:w-80">
            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
            <input v-model="search" type="search" placeholder="Buscar perfil..." aria-label="Buscar perfis"
                class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-9 pr-3 text-sm shadow-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/30" />
        </div>

        <div v-if="roles.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <article v-for="role in roles" :key="role.id"
                class="flex flex-col rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg"
                            :class="role.protected ? 'bg-cyan-100 text-cyan-700' : 'bg-gray-100 text-gray-600'">
                            <ShieldCheck class="h-5 w-5" />
                        </span>
                        <h2 class="text-base font-semibold text-gray-900">{{ role.name }}</h2>
                    </div>
                    <span v-if="role.protected" class="inline-flex items-center gap-1 rounded-full bg-cyan-50 px-2 py-0.5 text-[11px] font-medium text-cyan-700">
                        <Lock class="h-3 w-3" /> Protegido
                    </span>
                </div>

                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="inline-flex items-center gap-1.5 text-gray-500"><Users class="h-4 w-4" /> Usuários</dt>
                        <dd class="font-semibold text-gray-900 tabular-nums">{{ role.users_count }}</dd>
                    </div>
                    <div>
                        <div class="mb-1 flex items-center justify-between">
                            <dt class="text-gray-500">Permissões</dt>
                            <dd class="font-semibold text-gray-900 tabular-nums">
                                {{ role.protected ? "Todas" : `${role.permissions_count} de ${totalPermissions}` }}
                            </dd>
                        </div>
                        <div class="h-1.5 overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full bg-cyan-600" :style="{ width: `${role.protected ? 100 : coverage(role)}%` }" />
                        </div>
                    </div>
                </dl>

                <div class="mt-5 flex gap-2 border-t border-gray-100 pt-4">
                    <Link v-if="can('roles.edit')" :href="aclRoute('roles.edit', role.id)" class="flex-1">
                        <Button variant="outline" size="sm" class="w-full">
                            <template v-if="role.protected"><Eye /> Ver permissões</template>
                            <template v-else><Pencil /> Editar</template>
                        </Button>
                    </Link>
                    <Button v-if="can('roles.delete') && !role.protected" variant="ghost" size="icon-sm"
                        class="text-red-600 hover:bg-red-50" :aria-label="`Excluir perfil ${role.name}`"
                        :disabled="role.users_count > 0"
                        :title="role.users_count > 0 ? 'Remova o perfil dos usuários antes de excluir' : 'Excluir perfil'"
                        @click="askDelete(role)">
                        <Trash2 />
                    </Button>
                </div>
            </article>
        </div>

        <div v-else class="rounded-lg border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
            <ShieldCheck class="mx-auto h-10 w-10 text-gray-300" />
            <p class="mt-3 text-sm font-medium text-gray-700">
                {{ search ? "Nenhum perfil encontrado." : "Nenhum perfil cadastrado." }}
            </p>
        </div>

        <ConfirmDialog v-model:open="confirmOpen" title="Excluir perfil?"
            :description="`O perfil ${roleToDelete?.name ?? ''} será removido permanentemente.`"
            @confirm="deleteRole" />
    </AclLayout>
</template>
