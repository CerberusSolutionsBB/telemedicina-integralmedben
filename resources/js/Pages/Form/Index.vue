<script setup>
import { ref, watch } from "vue";
import { Head, router } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import CentralAdminLayout from "@/Layouts/CentralAdminLayout.vue";
import TableForm from "@/Components/TableForm.vue"; // Novo componente
import QuestionDialog from "@/Components/QuestionDialog.vue";

const props = defineProps({
    forms: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ status: '', search: '' }),
    },
    statusOptions: {
        type: Array,
        default: () => [],
    },
    can: {
        type: Object,
        default: () => ({ create: false, edit: false, delete: false, manage: false }),
    },
});

// Estados locais para filtros
const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');

let searchTimeout = null;

// Dialog states
const openFormDialog = ref(false);
const selectedForm = ref(null);

const openCreateFormDialog = () => {
    selectedForm.value = null;
    openFormDialog.value = true;
};

const openEditFormDialog = (form) => {
    selectedForm.value = form;
    openFormDialog.value = true;
};

const viewForm = (form) => {
    router.visit(route("forms.show", form.id));
};

const applyFilters = () => {
    router.get(
        route('forms.index'),
        {
            search: search.value,
            status: status.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
});

watch(status, () => {
    applyFilters();
});

const clearFilters = () => {
    search.value = '';
    status.value = '';
    applyFilters();
};
</script>

<template>

    <Head title="Formulários" />

    <CentralAdminLayout>
        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold">Formulários</h1>
                <p v-if="can.manage" class="text-sm text-gray-500 mt-1">
                    Visualizando todos os formulários (Modo Admin)
                </p>
            </div>
            <Button v-if="can.create" variant="primary" @click="openCreateFormDialog">
                Adicionar
            </Button>
        </div>

        <!-- Filtros -->
        <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm mb-4">
            <div class="flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                    <input v-model="search" type="text" placeholder="Buscar por título ou descrição..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-500" />
                </div>

                <div class="w-48">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select v-model="status"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-500 bg-white">
                        <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                </div>

                <Button v-if="search || status" variant="outline" @click="clearFilters" class="h-10">
                    Limpar
                </Button>
            </div>
        </div>

        <!-- Dialog -->
        <QuestionDialog v-model:open="openFormDialog" :question="selectedForm" />

        <!-- Tabela de Formulários -->
        <div class="bg-white border rounded-xl border-gray-200 shadow-sm overflow-hidden">
            <TableForm :forms="forms" :can="can" @edit-form="openEditFormDialog" @view-form="viewForm" />
        </div>

        <!-- Paginação -->
        <div v-if="forms.links && forms.links.length > 3" class="mt-4 flex items-center justify-between">
            <div class="text-sm text-gray-500">
                Mostrando {{ forms.from }} a {{ forms.to }} de {{ forms.total }} resultados
            </div>
            <div class="flex gap-1">
                <Button v-for="(link, index) in forms.links" :key="index" :variant="link.active ? 'primary' : 'outline'"
                    :disabled="!link.url"
                    @click="link.url && router.get(link.url, {}, { preserveState: true, preserveScroll: true })"
                    class="px-3 py-1 text-sm h-8" v-html="link.label" />
            </div>
        </div>
    </CentralAdminLayout>
</template>
