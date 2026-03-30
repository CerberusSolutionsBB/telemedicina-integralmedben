<script setup>
import { ref, watch, computed } from "vue";
import { Head, router } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import CentralAdminLayout from "@/Layouts/CentralAdminLayout.vue";
import TableForm from "@/Components/TableForm.vue";
import QuestionDialog from "@/Components/QuestionDialog.vue";
import { showToast } from '@/Utils/toast';
const props = defineProps({
    forms: { type: Object, required: true },
    filters: { type: Object, default: () => ({ status: '', search: '' }) },
    statusOptions: { type: Array, default: () => [] },
    can: { type: Object, default: () => ({ create: false, edit: false, delete: false, manage: false, toggleVisibility: false }) },
});
const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
let searchTimeout = null;
const openFormDialog = ref(false);
const selectedForm = ref(null);
const activatingId = ref(null);
const deletingId = ref(null);
const togglingVisibilityId = ref(null);
const STATUS_CONFIG = {
    'ativo': {
        label: 'ativo',
        color: 'green',
        bgColor: 'bg-green-100',
        textColor: 'text-green-700',
        canRespond: true
    },
    'rascunho': {
        label: 'em edição (rascunho)',
        color: 'gray',
        bgColor: 'bg-gray-100',
        textColor: 'text-gray-600',
        canRespond: false
    },
    'pausado': {
        label: 'pausado',
        color: 'yellow',
        bgColor: 'bg-yellow-100',
        textColor: 'text-yellow-600',
        canRespond: false
    },
    'encerrado': {
        label: 'encerrado',
        color: 'red',
        bgColor: 'bg-red-100',
        textColor: 'text-red-600',
        canRespond: false
    }
};
const getStatusConfig = (status) => STATUS_CONFIG[status] || {
    label: status,
    color: 'gray',
    canRespond: false
};
const inactiveCount = computed(() => {
    return props.forms.data?.filter(f => !getStatusConfig(f.status).canRespond).length || 0;
});
const inactiveByStatus = computed(() => {
    const counts = {};
    props.forms.data?.forEach(f => {
        if (!getStatusConfig(f.status).canRespond) {
            counts[f.status] = (counts[f.status] || 0) + 1;
        }
    });
    return counts;
});
const openCreateFormDialog = () => {
    selectedForm.value = null;
    openFormDialog.value = true;
};
const openEditFormDialog = (form) => {
    router.visit(route("forms.edit", form.id));
};
const viewForm = (form) => {
    router.visit(route("forms.show", form.id));
};
const viewFormPublic = (form) => {
    router.visit(route("forms.public.show", form.slug));
};
const activateForm = (form) => {
    activatingId.value = form.id;
    router.patch(
        route('forms.update-status', form.id),
        { status: 'ativo' },
        {
            preserveScroll: true,
            onFinish: () => {
                activatingId.value = null;
            },
            onSuccess: () => {
                showToast('Lei cadastrada com sucesso!', 'success');
                form.reset();
            },
            onError: (errors) => {
                console.error('Erro ao ativar formulário:', errors);
                showToast('Erro ao ativar: ' + Object.values(errors).join('\n'), 'error');
            }
        }
    );
};
const deleteForm = (form) => {
    deletingId.value = form.id;
    router.delete(route('forms.destroy', form.id), {
        preserveScroll: true,
        onFinish: () => {
            deletingId.value = null;
        },
        onSuccess: () => {
            showToast('Formulário excluído com sucesso!', 'success');
            openFormDialog.value = false;
            selectedForm.value = null;
        },
        onError: () => {
            showToast('Erro ao excluir formulário.', 'error');
        }
    });
};
const toggleVisibility = (form) => {
    togglingVisibilityId.value = form.id;
    router.patch(
        route('forms.toggle-visibility', form.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                togglingVisibilityId.value = null;
            },
            onSuccess: () => {
                const message = form.is_public
                    ? 'Formulário agora está privado.'
                    : 'Formulário agora está público.';
                showToast(message, 'success');
            },
            onError: (errors) => {
                showToast('Erro ao alterar visibilidade: ' + Object.values(errors).join('\n'), 'error');
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
watch([search, status], ([newSearch, newStatus], [oldSearch, oldStatus]) => {
    clearTimeout(searchTimeout);
    if (newSearch !== oldSearch) {
        searchTimeout = setTimeout(applyFilters, 300);
    } else {
        applyFilters();
    }
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
            <Button v-if="can.create" variant="primary" @click="router.visit(route('forms.create'))">
                Adicionar
            </Button>
        </div>
        <!-- ⭐ ALERTA INFORMATIVO: Regras de Status -->
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
                        Apenas formulários com status
                        <span class="font-bold text-green-700 bg-green-100 px-2 py-0.5 rounded">ATIVO</span>
                        podem receber respostas. Formulários em
                        <span class="text-gray-600">rascunho</span>,
                        <span class="text-yellow-600">pausados</span> ou
                        <span class="text-red-600">encerrados</span>
                        não são acessíveis ao público.
                    </p>
                </div>
            </div>
        </div>
        <!-- ⭐ ALERTA DE INATIVOS: Detalhado por Status -->
        <div v-if="inactiveCount > 0" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
            <div class="flex items-start gap-3">
                <svg class="h-6 w-6 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div class="flex-1">
                    <p class="text-sm font-medium text-yellow-800">
                        Atenção: {{ inactiveCount }} formulário(s) não {{ inactiveCount === 1 ? 'está' : 'estão' }}
                        ativo(s)
                    </p>
                    <!-- Detalhamento por status -->
                    <div class="mt-2 flex flex-wrap gap-2">
                        <span v-for="(count, statusKey) in inactiveByStatus" :key="statusKey"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                            :class="[getStatusConfig(statusKey).bgColor, getStatusConfig(statusKey).textColor]">
                            {{ getStatusConfig(statusKey).label }}: {{ count }}
                        </span>
                    </div>
                    <p class="text-xs text-yellow-700 mt-2">
                        {{ inactiveCount === 1 ? 'Este formulário não pode' : 'Estes formulários não podem' }}
                        receber respostas. Ative {{ inactiveCount === 1 ? 'o' : 'os' }} formulário{{ inactiveCount === 1
                            ? '' : 's' }}
                        para permitir o preenchimento.
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
        <!-- Dialogs -->
        <QuestionDialog v-model:open="openFormDialog" :question="selectedForm" />
        <!-- Tabela -->
        <div class="bg-white border rounded-xl border-gray-200 shadow-sm overflow-hidden">
            <TableForm :forms="forms" :can="can" :activating-id="activatingId" :deleting-id="deletingId"
                :toggling-visibility-id="togglingVisibilityId" @edit-form="openEditFormDialog" @view-form="viewForm"
                @view-form-public="viewFormPublic" @activate-form="activateForm" @delete-form="deleteForm"
                @toggle-visibility="toggleVisibility" />
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
