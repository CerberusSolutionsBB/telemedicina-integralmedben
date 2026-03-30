<script setup>
import CentralAdminLayout from '@/Layouts/CentralAdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Pencil, Trash2, Plus, Search, X, FileText } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import Button from '@/Components/ui/button/Button.vue';
import ConfirmDeleteModal from '@/Components/ConfirmDeleteModal.vue';
const props = defineProps({
    leis: {
        type: Object,
        default: () => ({
            data: [],
            links: [],
            from: 0,
            to: 0,
            total: 0
        })
    },
    filters: {
        type: Object,
        default: () => ({ search: '', type: '' })
    }
});
const search = ref(props.filters.search || '');
const selectedType = ref(props.filters.type || '');
const searchInput = ref(null);
let searchTimer = null;
const deleteModal = ref({
    show: false,
    lei: null,
    isProcessing: false
});
const tiposLeis = [
    { value: '', label: 'Todos os tipos' },
    { value: 'lgpd', label: 'LGPD' },
    { value: 'consolidada', label: 'Normas Consolidadas' },
    { value: 'codigo_etica', label: 'Código de Ética' },
    { value: 'politica_privacidade', label: 'Política de Privacidade' },
    { value: 'termo_uso', label: 'Termo de Uso' },
    { value: 'outro', label: 'Outro' }
];
const leisList = computed(() => props.leis?.data || []);
const paginationLinks = computed(() => props.leis?.links || []);
const hasLeis = computed(() => leisList.value.length > 0);
const hasSearch = computed(() => search.value.length > 0 || selectedType.value !== '');
const performSearch = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        const params = {};
        if (search.value) params.search = search.value;
        if (selectedType.value) params.type = selectedType.value;
        router.get(
            route('leis.index'),
            params,
            {
                preserveState: true,
                preserveScroll: true,
                replace: true
            }
        );
    }, 300);
};
watch(search, performSearch);
watch(selectedType, performSearch);
const clearSearch = () => {
    search.value = '';
    selectedType.value = '';
    searchInput.value?.focus();
};
const openDeleteModal = (lei) => {
    deleteModal.value = {
        show: true,
        lei: lei,
        isProcessing: false
    };
};
const closeDeleteModal = () => {
    deleteModal.value.show = false;
    deleteModal.value.lei = null;
    deleteModal.value.isProcessing = false;
};
const confirmDelete = () => {
    if (!deleteModal.value.lei) return;
    deleteModal.value.isProcessing = true;
    router.delete(route('leis.destroy', deleteModal.value.lei.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeDeleteModal();
        },
        onError: () => {
            deleteModal.value.isProcessing = false;
        },
        onFinish: () => {
            if (deleteModal.value.show) {
                deleteModal.value.isProcessing = false;
            }
        }
    });
};
const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('pt-BR');
};
const getTipoLabel = (tipo) => {
    const found = tiposLeis.find(t => t.value === tipo);
    return found?.label || tipo;
};
const getStatusClass = (status) => {
    const classes = {
        'ativo': 'bg-green-100 text-green-800',
        'inativo': 'bg-red-100 text-red-800',
        'rascunho': 'bg-yellow-100 text-yellow-800'
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};
</script>
<template>

    <Head title="Leis" />
    <CentralAdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 uppercase">
                Gerenciar Leis
            </h2>
        </template>
        <div class="py-6">
            <div class="mx-auto sm:px-6 lg:px-4 space-y-4">
                <!-- Barra de Ações: Busca + Filtro + Botão Adicionar -->
                <div class="flex flex-col lg:flex-row gap-3 justify-between items-start lg:items-center">
                    <!-- Busca e Filtro -->
                    <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                        <!-- Campo de Busca -->
                        <div class="relative w-full sm:w-80">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <Search class="h-5 w-5 text-gray-400" />
                            </div>
                            <input ref="searchInput" v-model="search" type="text" placeholder="Buscar por título..."
                                class="block w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm" />
                            <button v-if="hasSearch" @click="clearSearch"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                        <!-- Filtro de Tipo -->
                        <select v-model="selectedType"
                            class="block w-full sm:w-56 py-2.5 px-3 border border-gray-300 rounded-lg leading-5 bg-white focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm">
                            <option v-for="tipo in tiposLeis" :key="tipo.value" :value="tipo.value">
                                {{ tipo.label }}
                            </option>
                        </select>
                    </div>
                    <!-- Botão Adicionar -->
                    <Button
                        class="flex items-center gap-2 rounded-xl bg-cyan-500 hover:bg-cyan-600 px-5 py-2.5 text-white font-semibold shadow-md transition-colors whitespace-nowrap"
                        @click="router.visit(route('leis.create'))">
                        <Plus class="w-4 h-4" />
                        Adicionar Lei
                    </Button>
                </div>
                <!-- Indicador de busca ativa -->
                <div v-if="hasSearch && hasLeis" class="text-sm text-gray-600">
                    Mostrando resultados
                    <span v-if="search">para: <span class="font-medium text-cyan-700">"{{ search }}"</span></span>
                    <span v-if="selectedType" class="ml-1">tipo: <span class="font-medium text-cyan-700">{{
                        getTipoLabel(selectedType) }}</span></span>
                    <span class="text-gray-400 mx-2">|</span>
                    <span>{{ props.leis.total }} encontrado(s)</span>
                </div>
                <!-- Tabela -->
                <div class="border rounded-xl border-gray-200 bg-white shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Título
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Tipo
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Criado
                                        em</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500">Ações
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-for="lei in leisList" :key="lei.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">#{{ lei.id }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div class="font-medium">{{ lei.title }}</div>
                                        <div class="text-xs text-gray-500 truncate max-w-xs">
                                            {{ lei.user?.name || 'Sistema' }}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ getTipoLabel(lei.type) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        <span
                                            :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', getStatusClass(lei.status)]">
                                            {{ lei.status }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        {{ formatDate(lei.created_at) }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                        <div class="flex justify-end gap-2">
                                            <button @click="router.visit(route('leis.show', lei.id))"
                                                class="p-1.5 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-colors"
                                                title="Visualizar">
                                                <FileText class="w-4 h-4" />
                                            </button>
                                            <button @click="router.visit(route('leis.edit', lei.id))"
                                                class="p-1.5 text-cyan-600 hover:text-cyan-800 hover:bg-cyan-50 rounded-lg transition-colors"
                                                title="Editar">
                                                <Pencil class="w-4 h-4" />
                                            </button>
                                            <!-- BOTÃO DE DELETE ATUALIZADO -->
                                            <button @click="openDeleteModal(lei)"
                                                class="p-1.5 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors"
                                                title="Deletar">
                                                <Trash2 class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Empty State -->
                    <div v-if="!hasLeis" class="text-center py-12 text-gray-500">
                        <div v-if="hasSearch" class="space-y-2">
                            <Search class="w-12 h-12 mx-auto text-gray-300 mb-3" />
                            <p class="text-lg font-medium text-gray-900">Nenhum resultado encontrado</p>
                            <p class="text-sm">Não encontramos leis para os filtros selecionados</p>
                            <button @click="clearSearch"
                                class="mt-2 text-cyan-600 hover:text-cyan-800 font-medium text-sm">
                                Limpar filtros
                            </button>
                        </div>
                        <div v-else>
                            <FileText class="w-12 h-12 mx-auto text-gray-300 mb-3" />
                            <p class="text-lg font-medium text-gray-900">Nenhuma lei cadastrada</p>
                            <p class="text-sm text-gray-400">Clique em "Adicionar Lei" para começar</p>
                        </div>
                    </div>
                    <!-- Paginação -->
                    <div v-if="hasLeis && paginationLinks.length > 0"
                        class="flex items-center justify-between px-6 py-4 border-t border-gray-200 bg-gray-50">
                        <div class="text-sm text-gray-500">
                            Mostrando {{ props.leis.from || 0 }} a {{ props.leis.to || 0 }} de {{ props.leis.total || 0
                            }} leis
                        </div>
                        <div class="flex gap-1">
                            <template v-for="(link, index) in paginationLinks" :key="index">
                                <button v-if="link.url" @click="router.visit(link.url, { preserveScroll: true })"
                                    :class="[
                                        'px-3 py-1 rounded text-sm font-medium transition-colors',
                                        link.active
                                            ? 'bg-cyan-600 text-white'
                                            : 'bg-white text-gray-700 hover:bg-gray-100 border'
                                    ]" v-html="link.label" :disabled="link.active" />
                                <span v-else
                                    class="px-3 py-1 rounded text-sm text-gray-400 bg-gray-100 border cursor-default"
                                    v-html="link.label" />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ========================================== -->
        <!-- MODAL DE CONFIRMAÇÃO DE DELETE (NOVO) -->
        <!-- ========================================== -->
        <ConfirmDeleteModal :show="deleteModal.show" :item-name="deleteModal.lei?.title" title="Excluir Lei"
            message="Tem certeza que deseja mover esta lei para a lixeira?"
            warning-message="O documento será arquivado e poderá ser restaurado posteriormente. Esta ação não remove permanentemente os dados."
            confirm-text="Sim, Excluir" cancel-text="Cancelar" :is-processing="deleteModal.isProcessing"
            variant="danger" @close="closeDeleteModal" @confirm="confirmDelete" />
    </CentralAdminLayout>
</template>
