<script setup>
import CentralAdminLayout from '@/Layouts/CentralAdminLayout.vue';
import Button from '@/Components/ui/button/Button.vue';
import ConfirmDeleteModal from '@/Components/ConfirmDeleteModal.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { showToast } from '@/Utils/toast';
import {
    Search,
    X,
    ShieldAlert,
    Activity,
    CheckCircle,
    FileText,
    Plus,
    CreditCard,
    ChevronLeft,
    ChevronRight,
} from 'lucide-vue-next';

const props = defineProps({
    planos: {
        type: Array,
        default: () => [],
    },
    associados: {
        type: [Array, Object, null],
        default: null,
    },
    siprovError: {
        type: String,
        default: null,
    },
    pagination: {
        type: Object,
        default: () => ({ currentPage: 1, hasNextPage: false, total: 0 }),
    },
});

const page = usePage();

const flashMessage = computed(() => page.props.flash?.message);
const flashType = computed(() => page.props.flash?.type);

const can = computed(() => page.props?.authUser?.can?.siprov || {});

const currentSituacao = ref(new URLSearchParams(page.url?.split('?')[1] || '').get('situacaoBeneficio') || 'Ativo');
const currentPage = ref(props.pagination.currentPage || 1);

const goToPage = (newPage) => {
    if (newPage < 1) return;
    currentPage.value = newPage;
    const params = { situacaoBeneficio: currentSituacao.value, pagina: newPage };
    router.visit(route('siprov.index', params), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const extractItens = (data) => {
    if (Array.isArray(data) && data.length === 1 && data[0].itens) {
        return data[0].itens;
    }
    if (data?.itens) {
        return data.itens;
    }
    return Array.isArray(data) ? data : (data ? [data] : []);
};

const allAssociados = ref(extractItens(props.associados));

const search = ref('');
const selectedPlano = ref('');
const searchInput = ref(null);

const planoOptions = computed(() => {
    const planosSet = new Map();
    allAssociados.value.forEach((item) => {
        (item.planos || []).forEach((p) => {
            if (!planosSet.has(p.codPlano)) {
                planosSet.set(p.codPlano, { value: p.codPlano, label: p.nome });
            }
        });
    });
    return [
        { value: '', label: 'Todos os planos' },
        ...Array.from(planosSet.values()),
    ];
});

const filteredAssociados = computed(() => {
    return allAssociados.value.filter((item) => {
        const matchSearch = !search.value.trim() || (
            (item.nomePessoa || '').toLowerCase().includes(search.value.toLowerCase()) ||
            (item.cpfCnpj || '').includes(search.value) ||
            (item.email || '').toLowerCase().includes(search.value.toLowerCase()) ||
            (item.dataCadastro || '').includes(search.value) ||
            (item.dataAdesao || '').includes(search.value) ||
            (item.dataAtivacao || '').includes(search.value)
        );

        const matchPlano = !selectedPlano.value || (item.planos || []).some(
            (p) => String(p.codPlano) === String(selectedPlano.value)
        );

        return matchSearch && matchPlano;
    });
});

const hasResults = computed(() => filteredAssociados.value.length > 0);

const hasActiveFilters = computed(() => search.value.length > 0 || selectedPlano.value !== '' || currentSituacao.value !== 'Ativo');

const clearSearch = () => {
    search.value = '';
    selectedPlano.value = '';
    if (currentSituacao.value !== 'Ativo' || currentPage.value !== 1) {
        currentSituacao.value = 'Ativo';
        currentPage.value = 1;
        goToPage(1);
        return;
    }
    currentSituacao.value = 'Ativo';
    searchInput.value?.focus();
};

const onSituacaoChange = () => {
    currentPage.value = 1;
    router.visit(route('siprov.index', { situacaoBeneficio: currentSituacao.value }), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const formatDate = (date) => {
    if (!date) return '-';
    return date;
};

const navigateTo = (routeName, params = {}) => {
    router.visit(route(routeName, params));
};

const cartaoModal = ref({
    show: false,
    item: null,
    isProcessing: false,
});

const openCartaoModal = (item) => {
    cartaoModal.value = { show: true, item, isProcessing: false };
};

const closeCartaoModal = () => {
    if (cartaoModal.value.isProcessing) return;
    cartaoModal.value.show = false;
    setTimeout(() => {
        cartaoModal.value.item = null;
        cartaoModal.value.isProcessing = false;
    }, 200);
};

const confirmGerarCartao = async () => {
    const item = cartaoModal.value.item;
    if (!item) return;

    cartaoModal.value.isProcessing = true;

    const params = new URLSearchParams();

    params.append('nomePessoa', item.nomePessoa || '');
    params.append('cpfCnpj', item.cpfCnpj || '');
    params.append('email', item.email || '');
    params.append('telefoneCelular', item.telefoneCelular || '');
    params.append('codPessoa', item.codPessoa || '');
    params.append('codBeneficio', item.codBeneficio || '');
    params.append('dataCadastro', item.dataCadastro || '');
    params.append('dataAdesao', item.dataAdesao || '');
    params.append('dataAtivacao', item.dataAtivacao || '');
    params.append('situacao', item.situacao || '');

    (item.planos || []).forEach((plano, index) => {
        params.append(`planos[${index}][codPlano]`, plano.codPlano);
        params.append(`planos[${index}][nome]`, plano.nome);
    });

    try {
        const response = await fetch(route('siprov.cartao') + '?' + params.toString());
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'cartao-' + (item.codPessoa || 'associado') + '.pdf';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    } catch {
        showToast('Erro ao gerar cartão. Tente novamente.', 'error');
    } finally {
        cartaoModal.value.isProcessing = false;
        closeCartaoModal();
    }
};
</script>

<template>
    <Head title="Telemedicina - Associados" />

    <CentralAdminLayout>
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 uppercase tracking-wide">
                    Telemedicina
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Associados com benefício ativo na SIPROV.
                </p>
            </div>

            <div class="flex items-center gap-3">
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
                                placeholder="Buscar por nome, CPF, e-mail ou data (cadastro/adesão/ativação)..."
                                class="block w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg bg-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm transition-shadow"
                                @keyup.esc="clearSearch" />
                            <button v-if="hasActiveFilters" type="button" @click="clearSearch"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors">
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <select v-model="selectedPlano"
                            class="block w-full sm:w-64 py-2.5 px-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm transition-shadow cursor-pointer">
                            <option v-for="plano in planoOptions" :key="plano.value" :value="plano.value">
                                {{ plano.label }}
                            </option>
                        </select>

                        <select v-model="currentSituacao" @change="onSituacaoChange"
                            class="block w-full sm:w-48 py-2.5 px-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm transition-shadow cursor-pointer">
                            <option value="Ativo">Ativo</option>
                            <option value="Inativo">Inativo</option>
                            <option value="Suspenso">Suspenso</option>
                        </select>

                        <button v-if="hasActiveFilters" type="button" @click="clearSearch"
                            class="flex items-center gap-1 px-3 py-1 text-xs font-medium text-cyan-700 bg-cyan-100 rounded-full hover:bg-cyan-200 transition-colors">
                            <X class="w-3 h-3" />
                            Limpar filtros
                        </button>
                    </div>
                </div>

                <div class="border rounded-xl border-gray-200 bg-white shadow-sm overflow-hidden">
                    <div v-if="siprovError" class="p-6 text-center">
                        <div class="w-16 h-16 mx-auto bg-amber-50 rounded-full flex items-center justify-center mb-4">
                            <ShieldAlert class="w-8 h-8 text-amber-500" />
                        </div>
                        <p class="text-lg font-semibold text-gray-900 mb-2">Serviço SIPROV Indisponível</p>
                        <p class="text-sm text-gray-500 max-w-md mx-auto mb-4">
                            Não foi possível conectar à API da SIPROV. Verifique sua conexão com a internet e tente novamente.
                        </p>
                        <p class="text-xs text-gray-400 mb-4">Detalhes: {{ siprovError }}</p>
                        <Button variant="outline" size="sm" @click="navigateTo('siprov.index')">
                            <Activity class="w-4 h-4 mr-1" />
                            Tentar novamente
                        </Button>
                    </div>
                    <template v-else>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">#</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">Associado</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">CPF/CNPJ</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">Plano</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">Benefício</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">Cadastro</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">Situação</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500 tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-for="(item, idx) in filteredAssociados" :key="item.codPessoa + '-' + idx"
                                    class="hover:bg-gray-50 transition-colors group">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 font-mono">
                                        #{{ item.codPessoa }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="font-medium text-gray-900 group-hover:text-cyan-600 transition-colors">
                                            {{ item.nomePessoa }}
                                        </div>
                                        <div class="text-xs text-gray-500 truncate max-w-xs mt-0.5">
                                            {{ item.email || 'Sem e-mail' }}
                                        </div>
                                        <div v-if="item.telefoneCelular" class="text-xs text-gray-400 truncate max-w-xs mt-0.5">
                                            {{ item.telefoneCelular }}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                        {{ item.cpfCnpj || '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        <span v-for="(plano, pIdx) in item.planos" :key="pIdx"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100 mr-1 mb-1">
                                            <FileText class="w-3 h-3" />
                                            {{ plano.nome }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                        #{{ item.codBeneficio }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        <div>{{ formatDate(item.dataCadastro) }}</div>
                                        <div class="text-xs text-gray-400">Adesão: {{ formatDate(item.dataAdesao) }}</div>
                                        <div class="text-xs text-gray-400">Ativação: {{ formatDate(item.dataAtivacao) }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 border border-green-200">
                                            <CheckCircle class="w-3.5 h-3.5" />
                                            {{ item.situacao }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                        <div class="flex justify-end gap-1">
                                            <button @click="openCartaoModal(item)"
                                                class="p-2 text-cyan-600 hover:text-cyan-800 hover:bg-cyan-50 rounded-lg transition-all"
                                                title="Gerar Cartão">
                                                <CreditCard class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div v-if="hasResults && (currentPage > 1 || props.pagination.hasNextPage)"
                            class="flex items-center justify-between px-6 py-4 border-t border-gray-100">
                            <div class="text-sm text-gray-500">
                                Página {{ currentPage }} · {{ props.pagination.total }} resultado(s)
                            </div>
                            <div class="flex items-center gap-2">
                                <Button variant="outline" size="sm" :disabled="currentPage <= 1" @click="goToPage(currentPage - 1)">
                                    Anterior
                                </Button>
                                <Button variant="outline" size="sm" :disabled="!props.pagination.hasNextPage" @click="goToPage(currentPage + 1)">
                                    Próxima
                                </Button>
                            </div>
                        </div>

                        <div v-if="!hasResults" class="text-center py-16 text-gray-500">
                            <div v-if="hasActiveFilters" class="space-y-3">
                                <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center">
                                    <Search class="w-8 h-8 text-gray-400" />
                                </div>
                                <p class="text-lg font-medium text-gray-900">Nenhum resultado encontrado</p>
                                <p class="text-sm text-gray-500 max-w-sm mx-auto">
                                    Não encontramos associados para os filtros informados.
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
                                <p class="text-lg font-medium text-gray-900">Nenhum associado encontrado</p>
                                <p class="text-sm text-gray-500">
                                    Nenhum associado com benefício ativo foi retornado pela SIPROV.
                                </p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </CentralAdminLayout>
    <ConfirmDeleteModal :show="cartaoModal.show"
        title="Gerar Cartão de Benefício"
        :message="'Deseja gerar o cartão de benefício para ' + (cartaoModal.item?.nomePessoa || 'este associado') + '?'"
        warning-message="O cartão será baixado automaticamente como arquivo PDF."
        confirm-text="Sim, Gerar" cancel-text="Cancelar"
        :is-processing="cartaoModal.isProcessing"
        variant="info" @close="closeCartaoModal" @confirm="confirmGerarCartao" />
</template>
