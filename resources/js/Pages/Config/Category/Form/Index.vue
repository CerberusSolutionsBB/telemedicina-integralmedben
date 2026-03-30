<script setup>
import CentralAdminLayout from '@/Layouts/CentralAdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Pencil, Trash2, Plus, Search, X, FolderOpen } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import Button from '@/Components/ui/button/Button.vue';
import ConfirmDeleteModal from '@/Components/ConfirmDeleteModal.vue';

const props = defineProps({
    categories: {
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
        default: () => ({ search: '' })
    }
});

// Estado da busca (inicializa com valor da URL se existir)
const search = ref(props.filters.search || '');
const searchInput = ref(null);

// Debounce timer
let searchTimer = null;

// ==========================================
// ESTADO DO MODAL DE DELETE (NOVO)
// ==========================================
const deleteModal = ref({
    show: false,
    category: null,      // Objeto completo da categoria
    isProcessing: false
});

// Computed seguro
const categoriesList = computed(() => props.categories?.data || []);
const paginationLinks = computed(() => props.categories?.links || []);
const hasCategories = computed(() => categoriesList.value.length > 0);
const hasSearch = computed(() => search.value.length > 0);

// Busca com debounce (aguarda 300ms após digitar)
watch(search, (value) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(
            route('configuracoes.categories.forms.index'),
            { search: value },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true // Substitui URL sem criar novo histórico
            }
        );
    }, 300);
});

// Limpar busca
const clearSearch = () => {
    search.value = '';
    searchInput.value?.focus();
};

// ==========================================
// FUNÇÕES DO MODAL DE DELETE (NOVO)
// ==========================================

// Abre o modal com os dados da categoria
const openDeleteModal = (category) => {
    deleteModal.value = {
        show: true,
        category: category,
        isProcessing: false
    };
};

// Fecha o modal
const closeDeleteModal = () => {
    deleteModal.value.show = false;
    deleteModal.value.category = null;
    deleteModal.value.isProcessing = false;
};

// Confirma e executa a deleção
const confirmDelete = () => {
    if (!deleteModal.value.category) return;

    deleteModal.value.isProcessing = true;

    router.delete(route('configuracoes.categories.forms.destroy', deleteModal.value.category.id), {
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

// REMOVE A FUNÇÃO ANTIGA (substituída pelo modal)
// const deleteCategory = (id) => { ... } // DELETADA

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('pt-BR');
};
</script>

<template>

    <Head title="Categorias de Formulários" />

    <CentralAdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 uppercase">
                Gerenciar Categorias
            </h2>
        </template>

        <div class="py-6">
            <div class="mx-auto sm:px-6 lg:px-4 space-y-4">

                <!-- Barra de Ações: Busca + Botão Adicionar -->
                <div class="flex flex-col sm:flex-row gap-3 justify-between items-start sm:items-center">

                    <!-- Campo de Busca -->
                    <div class="relative w-full sm:w-96">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <Search class="h-5 w-5 text-gray-400" />
                        </div>
                        <input ref="searchInput" v-model="search" type="text"
                            placeholder="Buscar por nome ou descrição..."
                            class="block w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm transition duration-150 ease-in-out" />
                        <!-- Botão limpar (aparece quando tem texto) -->
                        <button v-if="hasSearch" @click="clearSearch"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer text-gray-400 hover:text-gray-600">
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Botão Adicionar -->
                    <Button
                        class="flex items-center gap-2 rounded-xl bg-cyan-500 hover:bg-cyan-600 px-5 py-2.5 text-white font-semibold shadow-md transition-colors whitespace-nowrap"
                        @click="router.visit(route('configuracoes.categories.forms.create'))">
                        <Plus class="w-4 h-4" />
                        Adicionar Categoria
                    </Button>
                </div>

                <!-- Indicador de busca ativa -->
                <div v-if="hasSearch && hasCategories" class="text-sm text-gray-600">
                    Mostrando resultados para: <span class="font-medium text-cyan-700">"{{ search }}"</span>
                    <span class="text-gray-400 mx-2">|</span>
                    <span>{{ props.categories.total }} encontrado(s)</span>
                </div>

                <!-- Tabela -->
                <div class="border rounded-xl border-gray-200 bg-white shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Nome
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Criado
                                        em</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500">Ações
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-for="category in categoriesList" :key="category.id"
                                    class="hover:bg-gray-50 transition-colors">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                        #{{ category.id }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 font-medium">
                                        <div class="flex items-center gap-2">
                                            <FolderOpen class="w-4 h-4 text-gray-400" />
                                            {{ category.name }}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        {{ formatDate(category.created_at) }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                        <div class="flex justify-end gap-2">
                                            <button
                                                @click="router.visit(route('configuracoes.categories.forms.edit', category.id))"
                                                class="p-1.5 text-cyan-600 hover:text-cyan-800 hover:bg-cyan-50 rounded-lg transition-colors"
                                                title="Editar">
                                                <Pencil class="w-4 h-4" />
                                            </button>

                                            <!-- BOTÃO DE DELETE ATUALIZADO -->
                                            <button @click="openDeleteModal(category)"
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

                    <!-- Empty State (busca sem resultados) -->
                    <div v-if="!hasCategories" class="text-center py-12 text-gray-500">
                        <div v-if="hasSearch" class="space-y-2">
                            <Search class="w-12 h-12 mx-auto text-gray-300 mb-3" />
                            <p class="text-lg font-medium text-gray-900">Nenhum resultado encontrado</p>
                            <p class="text-sm">Não encontramos categorias para "{{ search }}"</p>
                            <button @click="clearSearch"
                                class="mt-2 text-cyan-600 hover:text-cyan-800 font-medium text-sm">
                                Limpar busca
                            </button>
                        </div>
                        <div v-else>
                            <FolderOpen class="w-12 h-12 mx-auto text-gray-300 mb-3" />
                            <p class="text-lg font-medium text-gray-900">Nenhuma categoria cadastrada</p>
                            <p class="text-sm text-gray-400">Clique em "Adicionar Categoria" para começar</p>
                        </div>
                    </div>

                    <!-- Paginação -->
                    <div v-if="hasCategories && paginationLinks.length > 0"
                        class="flex items-center justify-between px-6 py-4 border-t border-gray-200 bg-gray-50">

                        <div class="text-sm text-gray-500">
                            Mostrando {{ props.categories.from || 0 }} a {{ props.categories.to || 0 }} de {{
                                props.categories.total || 0 }}
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
        <ConfirmDeleteModal :show="deleteModal.show" :item-name="deleteModal.category?.name" title="Excluir Categoria"
            message="Tem certeza que deseja excluir esta categoria?"
            warning-message="Esta ação não pode ser desfeita. Todos os formulários associados a esta categoria poderão ser afetados."
            confirm-text="Sim, Excluir" cancel-text="Cancelar" :is-processing="deleteModal.isProcessing"
            variant="danger" @close="closeDeleteModal" @confirm="confirmDelete" />
    </CentralAdminLayout>
</template>
