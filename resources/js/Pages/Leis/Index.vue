<script setup>
import CentralAdminLayout from '@/Layouts/CentralAdminLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Pencil, Trash2, Plus, Search, X, FileText, ShieldAlert } from 'lucide-vue-next';
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
    },
    tipos: {
        type: Array,
        default: () => []
    },
    can: {
        type: Object,
        default: () => ({
            create: false,
            edit: false,
            delete: false,
            view: false
        })
    }
});
const page = usePage();
const flashMessage = computed(() => page.props.flash?.message);
const flashType = computed(() => page.props.flash?.type);
const search = ref(props.filters.search || '');
const selectedType = ref(props.filters.type || '');
const searchInput = ref(null);
let searchTimer = null;
const deleteModal = ref({
    show: false,
    lei: null,
    isProcessing: false
});
const tiposLeis = computed(() => [
    { value: '', label: 'Todos os tipos' },
    ...(props.tipos || [
        { value: 'lgpd', label: 'LGPD' },
        { value: 'consolidada', label: 'Normas Consolidadas' },
        { value: 'codigo_etica', label: 'Código de Ética' },
        { value: 'politica_privacidade', label: 'Política de Privacidade' },
        { value: 'termo_uso', label: 'Termo de Uso' },
        { value: 'outro', label: 'Outro' }
    ])
]);
const leisList = computed(() => props.leis?.data || []);
const paginationLinks = computed(() => {
    return (props.leis?.links || []).map(link => ({
        ...link,
        label: link.label.replace('&laquo;', '‹').replace('&raquo;', '›')
    }));
});
const hasLeis = computed(() => leisList.value.length > 0);
const hasSearch = computed(() => search.value.length > 0 || selectedType.value !== '');
const hasActiveFilters = computed(() => hasSearch.value);
const performSearch = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        const params = {};
        if (search.value.trim()) params.search = search.value.trim();
        if (selectedType.value) params.type = selectedType.value;
        router.get(
            route('leis.index'),
            params,
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['leis', 'filters']
            }
        );
    }, 300);
};
watch(search, (newVal, oldVal) => {
    if (newVal !== oldVal) performSearch();
});
watch(selectedType, (newVal, oldVal) => {
    if (newVal !== oldVal) performSearch();
});
const clearSearch = () => {
    search.value = '';
    selectedType.value = '';
    searchInput.value?.focus();
    performSearch();
};
const openDeleteModal = (lei) => {
    if (!props.can.delete) {
        router.visit(route('unauthorized'));
        return;
    }
    deleteModal.value = {
        show: true,
        lei: lei,
        isProcessing: false
    };
};
const closeDeleteModal = () => {
    deleteModal.value.show = false;
    setTimeout(() => {
        deleteModal.value.lei = null;
        deleteModal.value.isProcessing = false;
    }, 200);
};
const confirmDelete = () => {
    if (!deleteModal.value.lei) return;
    deleteModal.value.isProcessing = true;
    router.delete(route('leis.destroy', deleteModal.value.lei.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeDeleteModal();
        },
        onError: (errors) => {
            const errorMessage = Object.values(errors).flat()[0] || 'Erro ao excluir';
            alert(errorMessage);
        },
        onFinish: () => {
            deleteModal.value.isProcessing = false;
        }
    });
};
const formatDate = (date) => {
    if (!date) return '-';
    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    }).format(new Date(date));
};
const formatDateTime = (date) => {
    if (!date) return '-';
    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(new Date(date));
};
const getTipoLabel = (tipo) => {
    const found = tiposLeis.value.find(t => t.value === tipo);
    return found?.label || tipo;
};
const getTipoIcon = (tipo) => {
    const icons = {
        'lgpd': '🔒',
        'consolidada': '📚',
        'codigo_etica': '⚖️',
        'politica_privacidade': '🛡️',
        'termo_uso': '📝',
        'outro': '📄'
    };
    return icons[tipo] || '📄';
};
const getStatusClass = (status) => {
    const classes = {
        'ativo': 'bg-green-100 text-green-800 border-green-200',
        'inativo': 'bg-red-100 text-red-800 border-red-200',
        'rascunho': 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'pendente': 'bg-orange-100 text-orange-800 border-orange-200'
    };
    return classes[status] || 'bg-gray-100 text-gray-800 border-gray-200';
};
const getStatusLabel = (status) => {
    const labels = {
        'ativo': 'Ativo',
        'inativo': 'Inativo',
        'rascunho': 'Rascunho',
        'pendente': 'Pendente'
    };
    return labels[status] || status;
};
const navigateTo = (routeName, params = {}) => {
    if (!props.can[routeName === 'leis.create' ? 'create' : 'edit']) {
        return;
    }
    router.visit(route(routeName, params));
};
</script>
<template>
    <Head :title="hasSearch ? `Busca: ${search} - Leis` : 'Gerenciar Leis'" />
    <CentralAdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 uppercase tracking-wide">
                        Gerenciar Leis
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ hasLeis ? `${props.leis.total} lei(s) cadastrada(s)` : 'Nenhuma lei cadastrada' }}
                    </p>
                </div>
                <div v-if="can.manage" class="flex items-center gap-2 text-xs text-cyan-600 bg-cyan-50 px-3 py-1 rounded-full">
                    <ShieldAlert class="w-4 h-4" />
                    <span>Modo Administrador</span>
                </div>
            </div>
        </template>
        <div class="py-6">
            <!-- ✅ Flash Messages -->
            <div v-if="flashMessage"
                 :class="[
                     'mb-4 p-4 rounded-lg text-sm font-medium',
                     flashType === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'
                 ]">
                {{ flashMessage }}
            </div>
            <div class="mx-auto sm:px-6 lg:px-4 space-y-4">
                <div class="flex flex-col lg:flex-row gap-3 justify-between items-start lg:items-center bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                        <div class="relative w-full sm:w-80">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <Search class="h-5 w-5 text-gray-400" />
                            </div>
                            <input
                                ref="searchInput"
                                v-model="search"
                                type="text"
                                placeholder="Buscar por título..."
                                class="block w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm transition-shadow"
                                @keyup.esc="clearSearch"
                            />
                            <button
                                v-if="hasSearch"
                                @click="clearSearch"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                        <select
                            v-model="selectedType"
                            class="block w-full sm:w-56 py-2.5 px-3 border border-gray-300 rounded-lg leading-5 bg-white focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm transition-shadow cursor-pointer"
                        >
                            <option v-for="tipo in tiposLeis" :key="tipo.value" :value="tipo.value">
                                {{ tipo.label }}
                            </option>
                        </select>
                        <!-- ✅ Badge de filtros ativos -->
                        <button
                            v-if="hasActiveFilters"
                            @click="clearSearch"
                            class="flex items-center gap-1 px-3 py-1 text-xs font-medium text-cyan-700 bg-cyan-100 rounded-full hover:bg-cyan-200 transition-colors"
                        >
                            <X class="w-3 h-3" />
                            Limpar filtros
                        </button>
                    </div>
                    <!-- Botão Adicionar -->
                    <Button
                        v-if="can.create"
                        class="flex items-center gap-2 rounded-xl bg-cyan-500 hover:bg-cyan-600 px-5 py-2.5 text-white font-semibold shadow-md transition-all hover:shadow-lg hover:scale-[1.02] active:scale-[0.98]"
                        @click="navigateTo('leis.create')"
                    >
                        <Plus class="w-4 h-4" />
                        Adicionar Lei
                    </Button>
                    <div
                        v-else
                        class="flex items-center gap-2 px-5 py-2.5 text-gray-400 text-sm bg-gray-100 rounded-xl cursor-not-allowed"
                        title="Você não tem permissão para criar leis"
                    >
                        <ShieldAlert class="w-4 h-4" />
                        Sem permissão
                    </div>
                </div>
                <!-- Indicador de busca -->
                <div v-if="hasActiveFilters && hasLeis" class="flex items-center gap-2 text-sm text-gray-600 bg-gray-50 p-3 rounded-lg">
                    <Search class="w-4 h-4 text-cyan-600" />
                    <span>
                        Mostrando {{ props.leis.total }} resultado(s)
                        <template v-if="search">
                            para "<span class="font-semibold text-cyan-700">{{ search }}</span>"
                        </template>
                        <template v-if="selectedType">
                            <span v-if="search">com tipo</span>
                            <span v-else>do tipo</span>
                            "<span class="font-semibold text-cyan-700">{{ getTipoLabel(selectedType) }}</span>"
                        </template>
                    </span>
                </div>
                <!-- Tabela -->
                <div class="border rounded-xl border-gray-200 bg-white shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">Título</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">Tipo</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">Autor</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">Criado em</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500 tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr
                                    v-for="lei in leisList"
                                    :key="lei.id"
                                    class="hover:bg-gray-50 transition-colors group"
                                >
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 font-mono">#{{ lei.id }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="font-medium text-gray-900 group-hover:text-cyan-600 transition-colors">{{ lei.title }}</div>
                                        <div v-if="lei.description" class="text-xs text-gray-500 truncate max-w-xs mt-0.5">
                                            {{ lei.description }}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                            <span>{{ getTipoIcon(lei.type) }}</span>
                                            {{ getTipoLabel(lei.type) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        <span :class="['inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border', getStatusClass(lei.status)]">
                                            {{ getStatusLabel(lei.status) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-gradient-to-br from-cyan-400 to-blue-500 flex items-center justify-center text-white text-xs font-bold">
                                                {{ lei.user?.name?.charAt(0).toUpperCase() || 'S' }}
                                            </div>
                                            <span class="truncate max-w-[120px]">{{ lei.user?.name || 'Sistema' }}</span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        <time :title="formatDateTime(lei.created_at)">
                                            {{ formatDate(lei.created_at) }}
                                        </time>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                        <div class="flex justify-end gap-1">
                                            <!-- Visualizar (todos podem) -->
                                            <button
                                                @click="router.visit(route('leis.show', lei.id))"
                                                class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-all"
                                                title="Visualizar"
                                            >
                                                <FileText class="w-4 h-4" />
                                            </button>
                                            <!-- Editar -->
                                            <button
                                                v-if="can.edit"
                                                @click="navigateTo('leis.edit', lei.id)"
                                                class="p-2 text-cyan-600 hover:text-cyan-800 hover:bg-cyan-50 rounded-lg transition-all"
                                                title="Editar"
                                            >
                                                <Pencil class="w-4 h-4" />
                                            </button>
                                            <span
                                                v-else
                                                class="p-2 text-gray-300 cursor-not-allowed"
                                                title="Sem permissão para editar"
                                            >
                                                <Pencil class="w-4 h-4" />
                                            </span>
                                            <!-- Deletar -->
                                            <button
                                                v-if="can.delete"
                                                @click="openDeleteModal(lei)"
                                                class="p-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-all"
                                                title="Excluir"
                                            >
                                                <Trash2 class="w-4 h-4" />
                                            </button>
                                            <span
                                                v-else
                                                class="p-2 text-gray-300 cursor-not-allowed"
                                                title="Sem permissão para excluir"
                                            >
                                                <Trash2 class="w-4 h-4" />
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="!hasLeis" class="text-center py-16 text-gray-500">
                        <div v-if="hasActiveFilters" class="space-y-3">
                            <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center">
                                <Search class="w-8 h-8 text-gray-400" />
                            </div>
                            <p class="text-lg font-medium text-gray-900">Nenhum resultado encontrado</p>
                            <p class="text-sm text-gray-500 max-w-sm mx-auto">
                                Não encontramos leis para "<span class="font-medium">{{ search }}</span>"
                                <span v-if="selectedType"> do tipo <span class="font-medium">{{ getTipoLabel(selectedType) }}</span></span>
                            </p>
                            <Button
                                variant="outline"
                                size="sm"
                                @click="clearSearch"
                                class="mt-2"
                            >
                                <X class="w-4 h-4 mr-1" />
                                Limpar filtros
                            </Button>
                        </div>
                        <div v-else class="space-y-3">
                            <div class="w-16 h-16 mx-auto bg-cyan-50 rounded-full flex items-center justify-center">
                                <FileText class="w-8 h-8 text-cyan-500" />
                            </div>
                            <p class="text-lg font-medium text-gray-900">Nenhuma lei cadastrada</p>
                            <p class="text-sm text-gray-500">Comece adicionando a primeira lei ao sistema</p>
                            <Button
                                v-if="can.create"
                                @click="navigateTo('leis.create')"
                                class="mt-2 bg-cyan-500 hover:bg-cyan-600"
                            >
                                <Plus class="w-4 h-4 mr-1" />
                                Adicionar Lei
                            </Button>
                        </div>
                    </div>
                    <div v-if="hasLeis && paginationLinks.length > 3" class="flex flex-col sm:flex-row items-center justify-between px-6 py-4 border-t border-gray-200 bg-gray-50 gap-4">
                        <div class="text-sm text-gray-500">
                            Mostrando <span class="font-medium">{{ props.leis.from || 0 }}</span> a
                            <span class="font-medium">{{ props.leis.to || 0 }}</span> de
                            <span class="font-medium">{{ props.leis.total || 0 }}</span> leis
                        </div>
                        <div class="flex gap-1">
                            <template v-for="(link, index) in paginationLinks" :key="index">
                                <button
                                    v-if="link.url"
                                    @click="router.visit(link.url, { preserveScroll: true })"
                                    :class="[
                                        'px-3 py-1.5 rounded-lg text-sm font-medium transition-all',
                                        link.active
                                            ? 'bg-cyan-600 text-white shadow-sm'
                                            : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'
                                    ]"
                                    v-html="link.label"
                                    :disabled="link.active"
                                />
                                <span
                                    v-else
                                    class="px-3 py-1.5 rounded-lg text-sm text-gray-400 bg-gray-100 border border-gray-200 cursor-default"
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <ConfirmDeleteModal
            :show="deleteModal.show"
            :item-name="deleteModal.lei?.title"
            title="Excluir Lei"
            message="Tem certeza que deseja mover esta lei para a lixeira?"
            warning-message="O documento será arquivado e poderá ser restaurado posteriormente."
            confirm-text="Sim, Excluir"
            cancel-text="Cancelar"
            :is-processing="deleteModal.isProcessing"
            variant="danger"
            @close="closeDeleteModal"
            @confirm="confirmDelete"
        />
    </CentralAdminLayout>
</template>
