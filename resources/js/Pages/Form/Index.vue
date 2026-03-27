<script setup>
import { ref, watch, computed } from "vue";
import { Head, router } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import CentralAdminLayout from "@/Layouts/CentralAdminLayout.vue";
import TableForm from "@/Components/TableForm.vue";
import QuestionDialog from "@/Components/QuestionDialog.vue";

const props = defineProps({
    forms: { type: Object, required: true },
    filters: { type: Object, default: () => ({ status: '', search: '' }) },
    statusOptions: { type: Array, default: () => [] },
    can: { type: Object, default: () => ({ create: false, edit: false, delete: false, manage: false }) },
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
let searchTimeout = null;

const openFormDialog = ref(false);
const selectedForm = ref(null);

// ⭐ Computed para contar inativos (tudo que não é "ativo")
const inactiveCount = computed(() => {
    return props.forms.data?.filter(f => f.status !== 'ativo').length || 0;
});

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

// ⭐ SÓ permite ver público se estiver ATIVO
const viewFormPublic = (form) => {
    if (form.status !== 'ativo') {
        const statusLabels = {
            'rascunho': 'em edição (rascunho)',
            'pausado': 'pausado',
            'encerrado': 'encerrado'
        };
        const label = statusLabels[form.status] || form.status;

        alert(`O formulário "${form.title}" está ${label}.\n\nAltere o status para "Ativo" nas configurações para permitir respostas.`);
        return;
    }

    router.visit(route("forms.public.show", form.slug));
};

// ⭐ Ativar formulário (muda status para "ativo")
const activateForm = (form) => {
    if (!confirm(`Deseja ativar o formulário "${form.title}"?\n\nEle poderá receber respostas públicas.`)) {
        return;
    }

    router.patch(
        route('forms.update-status', form.id),
        { status: 'ativo' },
        {
            preserveScroll: true,
            onSuccess: () => {
                alert('Formulário ativado com sucesso! Agora ele pode receber respostas.');
            },
            onError: (errors) => {
                alert('Erro ao ativar: ' + Object.values(errors).join('\n'));
            }
        }
    );
};

const applyFilters = () => {
    router.get(
        route('forms.index'),
        { search: search.value, status: status.value },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
});

watch(status, applyFilters);

const clearFilters = () => {
    search.value = '';
    status.value = '';
    applyFilters();
};
</script>

<template>

    <Head title="Formulários" />
    <CentralAdminLayout>
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold">Formulários</h1>
                <p v-if="can.manage" class="text-sm text-gray-500 mt-1">
                    Visualizando todos os formulários (Modo Admin)
                </p>
            </div>
            <Button v-if="can.create" variant="primary" @click="$inertia.visit(route('forms.create'))">
                Adicionar
            </Button>
        </div>

        <!-- ⭐ ALERTA: Apenas ATIVO recebe respostas -->
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4 rounded-r-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-800">
                        <span class="font-bold">Importante:</span>
                        Apenas formulários com status <span
                            class="font-bold text-green-700 bg-green-100 px-2 py-0.5 rounded">ATIVO</span>
                        podem receber respostas. Formulários em <span class="text-gray-600">rascunho</span>,
                        <span class="text-yellow-600">pausados</span> ou <span class="text-red-600">encerrados</span>
                        não são acessíveis ao público.
                    </p>
                </div>
            </div>
        </div>

        <!-- ⭐ ALERTA DE INATIVOS NA LISTA -->
        <div v-if="inactiveCount > 0" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
            <div class="flex items-center gap-3">
                <svg class="h-6 w-6 text-yellow-600 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <p class="text-sm font-medium text-yellow-800">
                        Atenção: {{ inactiveCount }} formulário(s) não {{ inactiveCount === 1 ? 'está' : 'estão' }}
                        ativo(s)
                    </p>
                    <p class="text-xs text-yellow-700 mt-0.5">
                        {{ inactiveCount === 1 ? 'Este formulário não pode' : 'Estes formulários não podem' }} receber
                        respostas.
                        Ative {{ inactiveCount === 1 ? 'o' : 'os' }} formulário{{ inactiveCount === 1 ? '' : 's' }} para
                        permitir o preenchimento.
                    </p>
                </div>
            </div>
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

        <QuestionDialog v-model:open="openFormDialog" :question="selectedForm" />

        <!-- Tabela -->
        <div class="bg-white border rounded-xl border-gray-200 shadow-sm overflow-hidden">
            <TableForm :forms="forms" :can="can" @edit-form="openEditFormDialog" @view-form="viewForm"
                @view-form-public="viewFormPublic" @activate-form="activateForm" />
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
