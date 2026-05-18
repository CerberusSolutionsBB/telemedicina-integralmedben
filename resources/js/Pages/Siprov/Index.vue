<script setup>
import CentralAdminLayout from '@/Layouts/CentralAdminLayout.vue';
import ConfirmDeleteModal from '@/Components/ConfirmDeleteModal.vue';
import Button from '@/Components/ui/button/Button.vue';
import { showToast } from '@/Utils/toast';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

import {
    Trash2,
    Search,
    X,
    ShieldAlert,
    Activity,
    CheckCircle,
    AlertCircle,
    Clock,
    Loader2,
    FileText,
    Plus,
} from 'lucide-vue-next';

const props = defineProps({
    siprovs: {
        type: Object,
        default: () => ({
            data: [],
            links: [],
            from: 0,
            to: 0,
            total: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            status: '',
            plano: '',
        }),
    },
    statuses: {
        type: Array,
        default: () => [],
    },
    planos: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();

const flashMessage = computed(() => page.props.flash?.message);
const flashType = computed(() => page.props.flash?.type);

const can = computed(() => page.props?.authUser?.can?.siprov || {});

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');
const selectedPlano = ref(props.filters.plano || '');
const searchInput = ref(null);

let searchTimer = null;

const deleteModal = ref({
    show: false,
    siprov: null,
    isProcessing: false,
});

const siprovList = computed(() => props.siprovs?.data || []);

const hasSiprovs = computed(() => siprovList.value.length > 0);

const hasActiveFilters = computed(() => {
    return (
        search.value.length > 0 ||
        selectedStatus.value !== '' ||
        selectedPlano.value !== ''
    );
});

const statusOptions = computed(() => [
    { value: '', label: 'Todos os status' },
    ...(props.statuses || []),
]);

const planoOptions = computed(() => [
    { value: '', label: 'Todos os planos' },
    ...(props.planos || []),
]);

const paginationLinks = computed(() => {
    return (props.siprovs?.links || []).map((link) => ({
        ...link,
        label: link.label.replace('&laquo;', '‹').replace('&raquo;', '›'),
    }));
});

const performSearch = () => {
    clearTimeout(searchTimer);

    searchTimer = setTimeout(() => {
        const params = {};

        if (search.value.trim()) {
            params.search = search.value.trim();
        }

        if (selectedStatus.value) {
            params.status = selectedStatus.value;
        }

        if (selectedPlano.value) {
            params.plano = selectedPlano.value;
        }

        router.get(route('siprov.index'), params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['siprovs', 'filters'],
        });
    }, 300);
};

watch(search, performSearch);
watch(selectedStatus, performSearch);
watch(selectedPlano, performSearch);

const clearSearch = () => {
    search.value = '';
    selectedStatus.value = '';
    selectedPlano.value = '';

    searchInput.value?.focus();

    router.get(
        route('siprov.index'),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['siprov', 'filters'],
        },
    );
};

const openDeleteModal = (siprov) => {
    if (!can.value?.delete) {
        router.visit(route('unauthorized'));
        return;
    }

    deleteModal.value = {
        show: true,
        siprov,
        isProcessing: false,
    };
};

const closeDeleteModal = () => {
    deleteModal.value.show = false;

    setTimeout(() => {
        deleteModal.value.siprov = null;
        deleteModal.value.isProcessing = false;
    }, 200);
};

const confirmDelete = () => {
    if (!deleteModal.value.siprov) return;

    deleteModal.value.isProcessing = true;

    router.delete(route('siprov.destroy', deleteModal.value.siprov.id), {
        preserveScroll: true,

        onSuccess: () => {
            closeDeleteModal();
            showToast('Integração SIPROV removida com sucesso.', 'success');
        },

        onError: () => {
            showToast('Erro ao remover integração SIPROV.', 'error');
        },

        onFinish: () => {
            deleteModal.value.isProcessing = false;
        },
    });
};

const viewSiprov = (siprov) => {
    if (!can.value?.view) return;

    router.visit(route('siprovs.show', siprov.id));
};

const formatDate = (date) => {
    if (!date) return '-';

    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    }).format(new Date(date));
};

const formatDateTime = (date) => {
    if (!date) return '-';

    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(date));
};

const getStatusLabel = (status) => {
    const found = statusOptions.value.find((item) => item.value === status);

    return found?.label || status || 'Desconhecido';
};

const getPlanoLabel = (codPlano) => {
    const found = planoOptions.value.find(
        (item) => String(item.value) === String(codPlano),
    );

    return found?.label || 'Plano não identificado';
};

const getStatusClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        processing: 'bg-blue-100 text-blue-800 border-blue-200',
        success: 'bg-green-100 text-green-800 border-green-200',
        failed: 'bg-red-100 text-red-800 border-red-200',
    };

    return classes[status] || 'bg-gray-100 text-gray-800 border-gray-200';
};

const getStatusIcon = (status) => {
    return {
        pending: Clock,
        processing: Loader2,
        success: CheckCircle,
        failed: AlertCircle,
    }[status] || Activity;
};
const navigateTo = (routeName, params = {}) => {
    router.visit(route(routeName, params));
};
</script>

<template>

    <Head :title="hasActiveFilters ? 'Busca - Integrações SIPROV' : 'Integrações SIPROV'" />

    <CentralAdminLayout>
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 uppercase tracking-wide">
                    Telemedicina
                </h2>

                <p class="text-sm text-gray-500 mt-1">
                    Controle de associados, benefícios e status de integração.
                </p>
            </div>

            <Button v-if="can.create"
                class="flex items-center gap-2 rounded-xl bg-cyan-500 hover:bg-cyan-600 px-5 py-2.5 text-white font-semibold shadow-md transition-all hover:shadow-lg hover:scale-[1.02] active:scale-[0.98]"
                @click="navigateTo('siprov.create')">
                <Plus class="w-4 h-4" />
                Adicionar
            </Button>
            <div v-else class="flex items-center gap-2 text-xs text-cyan-600 bg-cyan-50 px-3 py-1 rounded-full">
                <ShieldAlert class="w-4 h-4" />
                <span>Modo Administrador</span>
            </div>
        </div>

        <div class="py-6">
            <div v-if="flashMessage" :class="[
                'mb-4 p-4 rounded-lg text-sm font-medium',
                flashType === 'success'
                    ? 'bg-green-100 text-green-800 border border-green-200'
                    : 'bg-red-100 text-red-800 border border-red-200',
            ]">
                {{ flashMessage }}
            </div>

            <div class="mx-auto space-y-4">
                <div
                    class="flex flex-col xl:flex-row gap-3 justify-between items-start xl:items-center bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex flex-col sm:flex-row gap-3 w-full xl:w-auto">
                        <div class="relative w-full sm:w-96">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <Search class="h-5 w-5 text-gray-400" />
                            </div>

                            <input ref="searchInput" v-model="search" type="text"
                                placeholder="Buscar por nome, CPF, e-mail ou código..."
                                class="block w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg bg-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm transition-shadow"
                                @keyup.esc="clearSearch" />

                            <button v-if="hasActiveFilters" type="button" @click="clearSearch"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors">
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <select v-model="selectedStatus"
                            class="block w-full sm:w-56 py-2.5 px-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm transition-shadow cursor-pointer">
                            <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                                {{ status.label }}
                            </option>
                        </select>

                        <select v-model="selectedPlano"
                            class="block w-full sm:w-64 py-2.5 px-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm transition-shadow cursor-pointer">
                            <option v-for="plano in planoOptions" :key="plano.value" :value="plano.value">
                                {{ plano.label }}
                            </option>
                        </select>

                        <button v-if="hasActiveFilters" type="button" @click="clearSearch"
                            class="flex items-center gap-1 px-3 py-1 text-xs font-medium text-cyan-700 bg-cyan-100 rounded-full hover:bg-cyan-200 transition-colors">
                            <X class="w-3 h-3" />
                            Limpar filtros
                        </button>
                    </div>
                </div>

                <div v-if="hasActiveFilters && hasSiprovs"
                    class="flex items-center gap-2 text-sm text-gray-600 bg-gray-50 p-3 rounded-lg">
                    <Search class="w-4 h-4 text-cyan-600" />

                    <span>
                        Mostrando {{ props.siprovs.total }} resultado(s)

                        <template v-if="search">
                            para "<span class="font-semibold text-cyan-700">{{ search }}</span>"
                        </template>

                        <template v-if="selectedStatus">
                            com status "<span class="font-semibold text-cyan-700">
                                {{ getStatusLabel(selectedStatus) }}
                            </span>"
                        </template>
                    </span>
                </div>

                <div class="border rounded-xl border-gray-200 bg-white shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                        ID
                                    </th>

                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                        Associado
                                    </th>

                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                        CPF/CNPJ
                                    </th>

                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                        Plano
                                    </th>



                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                        Usuário
                                    </th>

                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                        Integrado em
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                        Status
                                    </th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                        Ações
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-for="siprov in siprovList" :key="siprov.id"
                                    class="hover:bg-gray-50 transition-colors group">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 font-mono">
                                        #{{ siprov.id }}
                                    </td>

                                    <td class="px-6 py-4 text-sm">
                                        <div
                                            class="font-medium text-gray-900 group-hover:text-cyan-600 transition-colors">
                                            {{ siprov.nome_pessoa }}
                                        </div>

                                        <div class="text-xs text-gray-500 truncate max-w-xs mt-0.5">
                                            {{ siprov.email || 'Sem e-mail' }}
                                        </div>

                                        <div class="text-xs text-gray-400 truncate max-w-xs mt-0.5">
                                            Código: {{ siprov.codigo_integracao }}
                                        </div>
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                        {{ siprov.cpf_formatado || siprov.cpf_cnpj || '-' }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                            <FileText class="w-3.5 h-3.5" />
                                            {{ siprov.plano_label || getPlanoLabel(siprov.cod_plano) }}
                                        </span>
                                    </td>



                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-6 h-6 rounded-full bg-gradient-to-br from-cyan-400 to-blue-500 flex items-center justify-center text-white text-xs font-bold">
                                                {{ siprov.user?.name?.charAt(0).toUpperCase() || 'S' }}
                                            </div>

                                            <span class="truncate max-w-[120px]">
                                                {{ siprov.user?.name || 'Sistema' }}
                                            </span>
                                        </div>
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        <time :title="formatDateTime(siprov.integrated_at || siprov.created_at)">
                                            {{ formatDate(siprov.integrated_at || siprov.created_at) }}
                                        </time>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        <span :class="[
                                            'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border',
                                            getStatusClass(siprov.status),
                                        ]">
                                            <component :is="getStatusIcon(siprov.status)" :class="[
                                                'w-3.5 h-3.5',
                                                siprov.status === 'processing' ? 'animate-spin' : '',
                                            ]" />

                                            {{ siprov.status_label || getStatusLabel(siprov.status) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                        <div class="flex justify-end gap-1">
                                            <button v-if="can?.view" type="button" @click="viewSiprov(siprov)"
                                                class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-all"
                                                title="Visualizar">
                                                <FileText class="w-4 h-4" />
                                            </button>

                                            <button v-if="can?.delete" type="button" @click="openDeleteModal(siprov)"
                                                class="p-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-all"
                                                title="Excluir">
                                                <Trash2 class="w-4 h-4" />
                                            </button>

                                            <span v-else class="p-2 text-gray-300 cursor-not-allowed"
                                                title="Sem permissão para excluir">
                                                <Trash2 class="w-4 h-4" />
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="!hasSiprovs" class="text-center py-16 text-gray-500">
                        <div v-if="hasActiveFilters" class="space-y-3">
                            <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center">
                                <Search class="w-8 h-8 text-gray-400" />
                            </div>

                            <p class="text-lg font-medium text-gray-900">
                                Nenhum resultado encontrado
                            </p>

                            <p class="text-sm text-gray-500 max-w-sm mx-auto">
                                Não encontramos integrações SIPROV para os filtros informados.
                            </p>

                            <Button variant="outline" size="sm" class="mt-2" @click="clearSearch">
                                <X class="w-4 h-4 mr-1" />
                                Limpar filtros
                            </Button>
                        </div>

                        <div v-else class="space-y-3">
                            <div class="w-16 h-16 mx-auto bg-cyan-50 rounded-full flex items-center justify-center">
                                <Activity class="w-8 h-8 text-cyan-500" />
                            </div>

                            <p class="text-lg font-medium text-gray-900">
                                Nenhuma integração SIPROV encontrada
                            </p>

                            <p class="text-sm text-gray-500">
                                As integrações aparecerão aqui após o envio de associados e benefícios.
                            </p>
                        </div>
                    </div>

                    <div v-if="hasSiprovs && paginationLinks.length > 3"
                        class="flex flex-col sm:flex-row items-center justify-between px-6 py-4 border-t border-gray-200 bg-gray-50 gap-4">
                        <div class="text-sm text-gray-500">
                            Mostrando
                            <span class="font-medium">{{ props.siprovs.from || 0 }}</span>
                            a
                            <span class="font-medium">{{ props.siprovs.to || 0 }}</span>
                            de
                            <span class="font-medium">{{ props.siprovs.total || 0 }}</span>
                            integrações
                        </div>

                        <div class="flex gap-1">
                            <template v-for="(link, index) in paginationLinks" :key="index">
                                <button v-if="link.url" type="button" :class="[
                                    'px-3 py-1.5 rounded-lg text-sm font-medium transition-all',
                                    link.active
                                        ? 'bg-cyan-600 text-white shadow-sm'
                                        : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200',
                                ]" :disabled="link.active" @click="router.visit(link.url, { preserveScroll: true })"
                                    v-html="link.label" />

                                <span v-else
                                    class="px-3 py-1.5 rounded-lg text-sm text-gray-400 bg-gray-100 border border-gray-200 cursor-default"
                                    v-html="link.label" />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmDeleteModal :show="deleteModal.show" :item-name="deleteModal.siprov?.nome_pessoa"
            title="Excluir Integração SIPROV" message="Tem certeza que deseja mover esta integração para a lixeira?"
            warning-message="O registro será removido da listagem, mas poderá ser restaurado posteriormente."
            confirm-text="Sim, excluir" cancel-text="Cancelar" :is-processing="deleteModal.isProcessing"
            variant="danger" @close="closeDeleteModal" @confirm="confirmDelete" />
    </CentralAdminLayout>
</template>
