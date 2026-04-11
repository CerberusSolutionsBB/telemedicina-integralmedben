<script setup>
import CentralAdminLayout from '@/Layouts/CentralAdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { showToast } from '@/Utils/toast';

import {
    Pencil,
    Trash2,
    Plus,
    Search,
    X,
    Shield,
    Key,
    CheckCircle2,
    XCircle,
    Clock,
    AlertCircle,
    RefreshCw,
    ChevronLeft,
    ChevronRight
} from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import Button from '@/Components/ui/button/Button.vue';
import ConfirmDeleteModal from '@/Components/ConfirmDeleteModal.vue';

const props = defineProps({
    credencias: {
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

const search = ref(props.filters.search || '');
const searchInput = ref(null);
let searchTimer = null;

const deleteModal = ref({
    show: false,
    credencial: null,
    isProcessing: false
});

const credenciasList = computed(() => props.credencias?.data || []);
const paginationLinks = computed(() => props.credencias?.links || []);
const hasCredencias = computed(() => credenciasList.value.length > 0);
const hasSearch = computed(() => search.value.length > 0);

// Debounce search
watch(search, (value) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(
            route('configuracoes.credencias_cluble.index'),
            { search: value },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true
            }
        );
    }, 300);
});

const clearSearch = () => {
    search.value = '';
    searchInput.value?.focus();
};

const openDeleteModal = (credencial) => {
    deleteModal.value = {
        show: true,
        credencial: credencial,
        isProcessing: false
    };
};

const closeDeleteModal = () => {
    deleteModal.value.show = false;
    deleteModal.value.credencial = null;
    deleteModal.value.isProcessing = false;
};

const confirmDelete = () => {
    if (!deleteModal.value.credencial) return;

    deleteModal.value.isProcessing = true;

    router.delete(
        route('configuracoes.credencias_cluble.destroy', deleteModal.value.credencial.id),
        {
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
        }
    );
};

// ✅ CORRIGIDO: Recebe a credencial como parâmetro
const refreshToken = (credencial) => {
    router.post(
        route('configuracoes.credencias_cluble.refresh', { credencial: credencial.id }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                showToast('Token atualizado com sucesso!', 'success');
            },
            onError: (errors) => {
                showToast(errors.message || 'Erro ao atualizar token', 'error');
            }
        }
    );
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('pt-BR');
};

const formatDateTime = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString('pt-BR');
};

// Verifica status do token
const getTokenStatus = (credencial) => {
    if (!credencial.token_expires_at) {
        return { label: 'Pendente', color: 'gray', icon: Clock };
    }

    const expiresAt = new Date(credencial.token_expires_at);
    const now = new Date();

    if (expiresAt < now) {
        return { label: 'Expirado', color: 'red', icon: XCircle };
    }

    // Expira em menos de 7 dias
    const sevenDays = 7 * 24 * 60 * 60 * 1000;
    if (expiresAt - now < sevenDays) {
        return { label: 'Expira em breve', color: 'yellow', icon: AlertCircle };
    }

    return { label: 'Válido', color: 'green', icon: CheckCircle2 };
};
</script>

<template>

    <Head title="Credenciais Cluble" />

    <CentralAdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 uppercase">
                Gerenciar Credenciais Cluble
            </h2>
        </template>

        <div class="py-6">
            <div class="mx-auto sm:px-6 lg:px-4 space-y-4">
                <!-- Header -->
                <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold">Credenciais Cluble</h1>
                        <p class="text-sm text-gray-500 mt-1">
                            Gerencie as credenciais de acesso à API do Clube Parcerias
                        </p>
                    </div>
                </div>

                <!-- Barra de Ações: Busca + Botão Adicionar -->
                <div class="flex flex-col sm:flex-row gap-3 justify-between items-start sm:items-center">
                    <!-- Campo de Busca -->
                    <div class="relative w-full sm:w-96">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <Search class="h-5 w-5 text-gray-400" />
                        </div>
                        <input ref="searchInput" v-model="search" type="text"
                            placeholder="Buscar por título ou client ID..."
                            class="block w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm transition duration-150 ease-in-out" />
                        <!-- Botão limpar -->
                        <button v-if="hasSearch" @click="clearSearch"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer text-gray-400 hover:text-gray-600">
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Botão Adicionar -->
                    <Button
                        class="flex items-center gap-2 rounded-xl bg-cyan-500 hover:bg-cyan-600 px-5 py-2.5 text-white font-semibold shadow-md transition-colors whitespace-nowrap"
                        @click="router.visit(route('configuracoes.credencias_cluble.create'))">
                        <Plus class="w-4 h-4" />
                        Adicionar Credencial
                    </Button>
                </div>

                <!-- Indicador de busca ativa -->
                <div v-if="hasSearch && hasCredencias" class="text-sm text-gray-600">
                    Mostrando resultados para: <span class="font-medium text-cyan-700">"{{ search }}"</span>
                    <span class="text-gray-400 mx-2">|</span>
                    <span>{{ props.credencias.total }} encontrado(s)</span>
                </div>

                <!-- Tabela -->
                <div class="border rounded-xl border-gray-200 bg-white shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        ID
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Título
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Client ID
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Grant Type
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Status Token
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Expira em
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500">
                                        Ações
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-for="credencial in credenciasList" :key="credencial.id"
                                    class="hover:bg-gray-50 transition-colors">
                                    <!-- ID -->
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                        #{{ credencial.id }}
                                    </td>

                                    <!-- Título -->
                                    <td class="px-6 py-4 text-sm text-gray-900 font-medium">
                                        <div class="flex items-center gap-2">
                                            <Shield class="w-4 h-4 text-cyan-600" />
                                            {{ credencial.title || 'Sem título' }}
                                        </div>
                                    </td>

                                    <!-- Client ID -->
                                    <td class="px-6 py-4 text-sm text-gray-500 font-mono text-xs">
                                        {{ credencial.client_id }}
                                    </td>

                                    <!-- Grant Type -->
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ credencial.grant_type }}
                                        </span>
                                    </td>

                                    <!-- Status Token -->
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        <div class="flex items-center gap-1.5">
                                            <component :is="getTokenStatus(credencial).icon" class="w-4 h-4" :class="{
                                                'text-green-500': getTokenStatus(credencial).color === 'green',
                                                'text-red-500': getTokenStatus(credencial).color === 'red',
                                                'text-yellow-500': getTokenStatus(credencial).color === 'yellow',
                                                'text-gray-400': getTokenStatus(credencial).color === 'gray'
                                            }" />
                                            <span class="text-sm" :class="{
                                                'text-green-700': getTokenStatus(credencial).color === 'green',
                                                'text-red-700': getTokenStatus(credencial).color === 'red',
                                                'text-yellow-700': getTokenStatus(credencial).color === 'yellow',
                                                'text-gray-500': getTokenStatus(credencial).color === 'gray'
                                            }">
                                                {{ getTokenStatus(credencial).label }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Expira em -->
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        {{ formatDateTime(credencial.token_expires_at) }}
                                    </td>

                                    <!-- Ações -->
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                        <div class="flex justify-end gap-2">
                                            <!-- Renovar Token -->
                                            <button @click="refreshToken(credencial)"
                                                class="p-1.5 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors"
                                                title="Renovar Token">
                                                <RefreshCw class="w-4 h-4" />
                                            </button>

                                            <!-- Editar -->
                                            <button
                                                @click="router.visit(route('configuracoes.credencias_cluble.edit', credencial.id))"
                                                class="p-1.5 text-cyan-600 hover:text-cyan-800 hover:bg-cyan-50 rounded-lg transition-colors"
                                                title="Editar">
                                                <Pencil class="w-4 h-4" />
                                            </button>

                                            <!-- Deletar -->
                                            <button @click="openDeleteModal(credencial)"
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
                    <div v-if="!hasCredencias" class="text-center py-12 text-gray-500">
                        <div v-if="hasSearch" class="space-y-2">
                            <Search class="w-12 h-12 mx-auto text-gray-300 mb-3" />
                            <p class="text-lg font-medium text-gray-900">Nenhum resultado encontrado</p>
                            <p class="text-sm">Não encontramos credenciais para "{{ search }}"</p>
                            <button @click="clearSearch"
                                class="mt-2 text-cyan-600 hover:text-cyan-800 font-medium text-sm">
                                Limpar busca
                            </button>
                        </div>
                        <div v-else>
                            <Shield class="w-12 h-12 mx-auto text-gray-300 mb-3" />
                            <p class="text-lg font-medium text-gray-900">Nenhuma credencial cadastrada</p>
                            <p class="text-sm text-gray-400">Clique em "Adicionar Credencial" para começar</p>
                        </div>
                    </div>

                    <!-- Paginação -->
                    <div v-if="hasCredencias && paginationLinks.length > 0"
                        class="flex items-center justify-between px-6 py-4 border-t border-gray-200 bg-gray-50">
                        <div class="text-sm text-gray-500">
                            Mostrando {{ props.credencias.from || 0 }} a {{ props.credencias.to || 0 }} de {{
                                props.credencias.total || 0 }}
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

        <!-- Modal de Confirmação de Delete -->
        <ConfirmDeleteModal :show="deleteModal.show"
            :item-name="deleteModal.credencial?.title || deleteModal.credencial?.client_id" title="Excluir Credencial"
            message="Tem certeza que deseja excluir esta credencial?"
            warning-message="Esta ação não pode ser desfeita. A integração com a API do Clube será interrompida."
            confirm-text="Sim, Excluir" cancel-text="Cancelar" :is-processing="deleteModal.isProcessing"
            variant="danger" @close="closeDeleteModal" @confirm="confirmDelete" />
    </CentralAdminLayout>
</template>
