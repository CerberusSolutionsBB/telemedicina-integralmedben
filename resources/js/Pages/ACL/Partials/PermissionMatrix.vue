<script setup>
import { computed, ref } from "vue";
import { Lock, Search } from "lucide-vue-next";
import { actionLabel, groupPermissions, permissionAction } from "./useAcl";

const props = defineProps({
    // Todas as permissões disponíveis (nomes)
    permissions: { type: Array, required: true },
    // Permissões herdadas (ex.: vindas dos perfis do usuário) — exibidas marcadas e bloqueadas
    inherited: { type: Array, default: () => [] },
    disabled: { type: Boolean, default: false },
});

const model = defineModel({ type: Array, default: () => [] });

const filter = ref("");

const inheritedSet = computed(() => new Set(props.inherited));
const selectedSet = computed(() => new Set(model.value));

const groups = computed(() => {
    const term = filter.value.trim().toLowerCase();
    return groupPermissions(props.permissions)
        .map((group) => ({
            ...group,
            permissions: term
                ? group.permissions.filter((p) =>
                    p.includes(term) || group.label.toLowerCase().includes(term) || actionLabel(permissionAction(p)).toLowerCase().includes(term))
                : group.permissions,
        }))
        .filter((group) => group.permissions.length);
});

const isChecked = (name) => selectedSet.value.has(name) || inheritedSet.value.has(name);
const isLocked = (name) => props.disabled || inheritedSet.value.has(name);

const toggle = (name) => {
    if (isLocked(name)) return;
    model.value = selectedSet.value.has(name)
        ? model.value.filter((p) => p !== name)
        : [...model.value, name];
};

const editable = (group) => group.permissions.filter((p) => !isLocked(p));
const groupState = (group) => {
    const total = group.permissions.length;
    const checked = group.permissions.filter(isChecked).length;
    return { total, checked, all: checked === total, some: checked > 0 && checked < total };
};

const toggleGroup = (group) => {
    const names = editable(group);
    if (!names.length) return;
    const allSelected = names.every((p) => selectedSet.value.has(p));
    model.value = allSelected
        ? model.value.filter((p) => !names.includes(p))
        : [...new Set([...model.value, ...names])];
};

const totalChecked = computed(() => props.permissions.filter(isChecked).length);
</script>

<template>
    <div>
        <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-gray-500">
                <span class="font-semibold text-gray-900">{{ totalChecked }}</span>
                de {{ permissions.length }} permissões selecionadas
            </p>
            <div class="relative w-full sm:w-64">
                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                <input v-model="filter" type="search" placeholder="Filtrar permissões..." aria-label="Filtrar permissões"
                    class="w-full rounded-lg border border-gray-300 py-2 pl-9 pr-3 text-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/30" />
            </div>
        </div>

        <div v-if="groups.length" class="grid gap-3 md:grid-cols-2">
            <fieldset v-for="group in groups" :key="group.module"
                class="rounded-lg border border-gray-200 bg-white">
                <legend class="sr-only">{{ group.label }}</legend>
                <div class="flex items-center justify-between gap-2 border-b border-gray-100 bg-gray-50 px-3 py-2 rounded-t-lg">
                    <span class="text-sm font-semibold text-gray-800">{{ group.label }}</span>
                    <div class="flex items-center gap-2">
                        <span class="text-xs tabular-nums text-gray-500">
                            {{ groupState(group).checked }}/{{ groupState(group).total }}
                        </span>
                        <button v-if="!disabled && editable(group).length" type="button"
                            class="rounded px-2 py-0.5 text-xs font-medium text-cyan-700 hover:bg-cyan-50"
                            @click="toggleGroup(group)">
                            {{ editable(group).every((p) => selectedSet.has(p)) ? "Desmarcar" : "Marcar todas" }}
                        </button>
                    </div>
                </div>
                <ul class="divide-y divide-gray-50 p-1">
                    <li v-for="name in group.permissions" :key="name">
                        <label class="flex items-center gap-3 rounded-md px-2 py-1.5"
                            :class="isLocked(name) ? 'cursor-not-allowed' : 'cursor-pointer hover:bg-gray-50'">
                            <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-cyan-600 focus:ring-cyan-500 disabled:opacity-60"
                                :checked="isChecked(name)" :disabled="isLocked(name)" @change="toggle(name)" />
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm text-gray-800">{{ actionLabel(permissionAction(name)) }}</span>
                                <span class="block truncate font-mono text-[11px] text-gray-400">{{ name }}</span>
                            </span>
                            <span v-if="inheritedSet.has(name)"
                                class="inline-flex items-center gap-1 rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-500"
                                title="Concedida por um perfil do usuário">
                                <Lock class="h-3 w-3" /> via perfil
                            </span>
                        </label>
                    </li>
                </ul>
            </fieldset>
        </div>
        <p v-else class="rounded-lg border border-dashed border-gray-300 py-8 text-center text-sm text-gray-500">
            Nenhuma permissão encontrada.
        </p>
    </div>
</template>
