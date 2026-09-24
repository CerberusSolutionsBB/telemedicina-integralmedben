<script setup>
import CentralAdminLayout from '@/Layouts/CentralAdminLayout.vue';
import Button from '@/Components/ui/button/Button.vue';
import ConfirmDeleteModal from '@/Components/ConfirmDeleteModal.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, computed, reactive, watch } from 'vue';
import { showToast } from '@/Utils/toast';
import {
    Search,
    X,
    ShieldAlert,
    Activity,
    FileText,
    Plus,
    CreditCard,
    ChevronLeft,
    ChevronRight,
    Building2,
    SlidersHorizontal,
    ChevronDown,
    ExternalLink,
    UserX,
    UserCheck,
    Users,
    Loader2,
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
});

const page = usePage();

const flashMessage = computed(() => page.props.flash?.message);
const flashType = computed(() => page.props.flash?.type);

const can = computed(() => page.props?.authUser?.can?.siprov || {});

const DEFAULT_SITUACAO = 'Todos';

const currentSituacao = ref(new URLSearchParams(page.url?.split('?')[1] || '').get('situacaoBeneficio') || DEFAULT_SITUACAO);
const PER_PAGE = 15;
const currentPage = ref(1);

const isLoading = ref(false);

// Recarrega só os dados da SIPROV, descartando a lista anterior enquanto carrega.
const loadAssociados = (params) => {
    router.visit(route('siprov.index', params), {
        only: ['associados', 'siprovError'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onStart: () => {
            isLoading.value = true;
            allAssociados.value = [];
            expandedCards.clear();
        },
        onFinish: () => {
            isLoading.value = false;
        },
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

watch(() => props.associados, (value) => {
    allAssociados.value = extractItens(value);
    currentPage.value = 1;
});

const search = ref('');
const selectedPlano = ref('');
const selectedParceiro = ref('');
const searchInput = ref(null);
const filtersOpen = ref(false);
const expandedCards = reactive(new Set());

const situacaoClass = (situacao) => {
    const key = (situacao || '').toLowerCase();
    const map = {
        ativo: 'bg-green-100 text-green-700 border-green-200',
        suspenso: 'bg-amber-100 text-amber-700 border-amber-200',
        inativo: 'bg-red-100 text-red-700 border-red-200',
    };
    return map[key] || 'bg-gray-100 text-gray-600 border-gray-200';
};

const situacaoDot = (situacao) => {
    const key = (situacao || '').toLowerCase();
    const map = {
        ativo: 'bg-green-500',
        suspenso: 'bg-amber-500',
        inativo: 'bg-red-500',
    };
    return map[key] || 'bg-gray-400';
};

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

const parceiroOptions = computed(() => {
    const parceirosSet = new Map();
    allAssociados.value.forEach((item) => {
        (item.tenants || []).forEach((t) => {
            const descricao = t.details?.[0]?.descricao || t.tenant_domain;
            if (descricao && !parceirosSet.has(descricao)) {
                parceirosSet.set(descricao, { value: descricao, label: descricao });
            }
        });
    });
    return [
        { value: '', label: 'Todos os parceiros' },
        ...Array.from(parceirosSet.values()),
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

        const matchParceiro = !selectedParceiro.value || (item.tenants || []).some(
            (t) => (t.details?.[0]?.descricao || t.tenant_domain) === selectedParceiro.value
        );

        return matchSearch && matchPlano && matchParceiro;
    });
});

const hasResults = computed(() => filteredAssociados.value.length > 0);

// ═══ Paginação local (a lista completa já vem da SIPROV) ═══
const totalPages = computed(() => Math.max(1, Math.ceil(filteredAssociados.value.length / PER_PAGE)));

const paginatedAssociados = computed(() => {
    const start = (currentPage.value - 1) * PER_PAGE;
    return filteredAssociados.value.slice(start, start + PER_PAGE);
});

const pageRange = computed(() => {
    const start = (currentPage.value - 1) * PER_PAGE + 1;
    const end = Math.min(currentPage.value * PER_PAGE, filteredAssociados.value.length);
    return { start, end };
});

// Números de página com reticências: 1 … 4 5 6 … 10
const pageNumbers = computed(() => {
    const total = totalPages.value;
    const current = currentPage.value;
    if (total <= 7) {
        return Array.from({ length: total }, (_, i) => i + 1);
    }
    const pages = [1];
    const from = Math.max(2, current - 1);
    const to = Math.min(total - 1, current + 1);
    if (from > 2) pages.push('…');
    for (let p = from; p <= to; p++) pages.push(p);
    if (to < total - 1) pages.push('…');
    pages.push(total);
    return pages;
});

const goToPage = (newPage) => {
    if (newPage < 1 || newPage > totalPages.value) return;
    currentPage.value = newPage;
};

watch([search, selectedPlano, selectedParceiro], () => {
    currentPage.value = 1;
});

// Se a lista encolher (ex.: associado removido ao inativar), não deixa a página vazia.
watch(totalPages, (total) => {
    if (currentPage.value > total) currentPage.value = total;
});

const hasActiveFilters = computed(() =>
    search.value.length > 0 ||
    selectedPlano.value !== '' ||
    selectedParceiro.value !== '' ||
    currentSituacao.value !== DEFAULT_SITUACAO
);

const activeFilterChips = computed(() => {
    const chips = [];
    if (selectedPlano.value) {
        const plano = planoOptions.value.find(p => p.value === selectedPlano.value);
        if (plano) chips.push({ label: `Plano: ${plano.label}`, type: 'plano' });
    }
    if (selectedParceiro.value) {
        chips.push({ label: `Parceiro: ${selectedParceiro.value}`, type: 'parceiro' });
    }
    return chips;
});

const removeFilter = (type) => {
    if (type === 'plano') selectedPlano.value = '';
    if (type === 'parceiro') selectedParceiro.value = '';
};

const clearAllFilters = () => {
    search.value = '';
    selectedPlano.value = '';
    selectedParceiro.value = '';
    if (currentSituacao.value !== DEFAULT_SITUACAO) {
        currentSituacao.value = DEFAULT_SITUACAO;
        onSituacaoChange();
    }
};

const toggleCard = (id) => {
    if (expandedCards.has(id)) {
        expandedCards.delete(id);
    } else {
        expandedCards.add(id);
    }
};

const clearSearch = () => {
    search.value = '';
    selectedPlano.value = '';
    selectedParceiro.value = '';
    filtersOpen.value = false;
    currentPage.value = 1;
    if (currentSituacao.value !== DEFAULT_SITUACAO) {
        currentSituacao.value = DEFAULT_SITUACAO;
        onSituacaoChange();
        return;
    }
    searchInput.value?.focus();
};

const situacaoOptions = [
    { value: DEFAULT_SITUACAO, label: 'Todos' },
    { value: 'Ativo', label: 'Ativo' },
    { value: 'Inativo', label: 'Inativo' },
];

const setSituacao = (value) => {
    if (currentSituacao.value === value) return;
    currentSituacao.value = value;
    onSituacaoChange();
};

const onSituacaoChange = () => {
    loadAssociados({ situacaoBeneficio: currentSituacao.value });
};

const formatDate = (date) => {
    if (!date) return '-';
    return date;
};

const formatCpf = (cpf) => {
    if (!cpf) return '-';
    const nums = cpf.replace(/\D/g, '');
    if (nums.length === 11) {
        return nums.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    }
    return cpf;
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

const isAtivo = (item) => (item?.situacao || '').toLowerCase() === 'ativo';
const isInativo = (item) => (item?.situacao || '').toLowerCase() === 'inativo';
const podeAlterarSituacao = (item) => can.value.delete && (isAtivo(item) || isInativo(item));

// ═══ Ativar / inativar associado (grava o benefício com ativo: true/false) ═══
const situacaoModal = ref({
    show: false,
    item: null,
    ativar: false,
    isProcessing: false,
});

const openSituacaoModal = (item) => {
    situacaoModal.value = { show: true, item, ativar: !isAtivo(item), isProcessing: false };
};

const closeSituacaoModal = () => {
    if (situacaoModal.value.isProcessing) return;
    situacaoModal.value.show = false;
    setTimeout(() => {
        situacaoModal.value.item = null;
    }, 200);
};

const confirmAlterarSituacao = async () => {
    const { item, ativar } = situacaoModal.value;
    if (!item) return;

    situacaoModal.value.isProcessing = true;

    try {
        const { data } = await axios.put(route('siprov.situacao', item.codBeneficio), {
            cpf: item.cpfCnpj,
            codPlano: item.planos?.[0]?.codPlano,
            ativo: ativar,
        });
        showToast(data.message || 'Situação alterada com sucesso.', 'success');
        if (currentSituacao.value === DEFAULT_SITUACAO) {
            item.situacao = ativar ? 'ATIVO' : 'INATIVO';
        } else {
            allAssociados.value = allAssociados.value.filter((a) => a.codBeneficio !== item.codBeneficio);
        }
    } catch (error) {
        showToast(error.response?.data?.message || 'Erro ao alterar situação do associado.', 'error');
    } finally {
        situacaoModal.value.isProcessing = false;
        closeSituacaoModal();
    }
};

// ═══ Dependentes (ativar / inativar = regravar com ativo: true/false) ═══
const dependentesModal = ref({
    show: false,
    item: null,
    loading: false,
    error: null,
    dependentes: [],
    confirmingCod: null,
    processingCod: null,
});

const openDependentesModal = async (item) => {
    dependentesModal.value = {
        show: true,
        item,
        loading: true,
        error: null,
        dependentes: [],
        confirmingCod: null,
        processingCod: null,
    };

    try {
        const { data } = await axios.get(route('siprov.dependentes', item.codBeneficio));
        dependentesModal.value.dependentes = data.dependentes || [];
    } catch (error) {
        dependentesModal.value.error = error.response?.data?.message || 'Erro ao carregar dependentes.';
    } finally {
        dependentesModal.value.loading = false;
    }
};

const closeDependentesModal = () => {
    if (dependentesModal.value.processingCod) return;
    dependentesModal.value.show = false;
};

const alterarSituacaoDependente = async (dependente) => {
    const item = dependentesModal.value.item;
    if (!item) return;

    const ativar = !dependente.ativo;
    dependentesModal.value.processingCod = dependente.codDependente;

    try {
        const { data } = await axios.put(route('siprov.situacao-dependente', {
            codBeneficio: item.codBeneficio,
            codDependente: dependente.codDependente,
        }), { ativo: ativar });
        dependente.ativo = ativar;
        showToast(data.message || 'Situação do dependente alterada com sucesso.', 'success');
    } catch (error) {
        showToast(error.response?.data?.message || 'Erro ao alterar situação do dependente.', 'error');
    } finally {
        dependentesModal.value.processingCod = null;
        dependentesModal.value.confirmingCod = null;
    }
};
</script>

<template>

    <Head title="Telemedicina - Associados" />

    <CentralAdminLayout>
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-lg sm:text-xl font-semibold text-gray-800 uppercase tracking-wide">
                    Telemedicina
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Associados com benefício na SIPROV.
                </p>
            </div>


            <div class="flex items-center gap-3">
                <Button v-if="can.create"
                    class="flex items-center justify-center gap-2 w-full sm:w-auto rounded-xl bg-cyan-500 hover:bg-cyan-600 px-5 py-2.5 text-white font-semibold shadow-md transition-all hover:shadow-lg hover:scale-[1.02] active:scale-[0.98]"
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

            <!-- ═══ FILTROS ═══ -->
            <div class="mx-auto space-y-3">
                <div class="inline-flex w-full sm:w-auto rounded-lg border border-gray-200 bg-white p-1 shadow-sm">
                    <button v-for="opt in situacaoOptions" :key="opt.value" type="button"
                        :disabled="isLoading" @click="setSituacao(opt.value)" :class="[
                            'flex-1 sm:flex-none px-4 py-2 rounded-md text-sm font-medium transition-colors min-h-[40px] disabled:cursor-wait',
                            currentSituacao === opt.value
                                ? 'bg-cyan-500 text-white shadow-sm'
                                : 'text-gray-600 hover:bg-gray-50',
                        ]">
                        {{ opt.label }}
                    </button>
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <Search class="h-5 w-5 text-gray-400" />
                        </div>
                        <input ref="searchInput" v-model="search" type="text" placeholder="Buscar associado..."
                            class="block w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg bg-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm transition-shadow"
                            @keyup.esc="clearAllFilters" />
                        <button v-if="hasActiveFilters" type="button" @click="clearAllFilters"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors">
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <button @click="filtersOpen = !filtersOpen" :class="[
                        'flex items-center gap-2 px-4 py-2.5 rounded-lg border text-sm font-medium transition-colors shrink-0',
                        filtersOpen || hasActiveFilters
                            ? 'border-cyan-300 bg-cyan-50 text-cyan-700'
                            : 'border-gray-300 bg-white text-gray-600 hover:bg-gray-50',
                    ]">
                        <SlidersHorizontal class="w-4 h-4" />
                        <span class="hidden sm:inline">Filtros</span>
                        <ChevronDown :class="['w-4 h-4 transition-transform', filtersOpen ? 'rotate-180' : '']" />
                    </button>
                </div>

                <!-- Chips de filtros ativos -->
                <div v-if="activeFilterChips.length" class="flex flex-wrap items-center gap-2">
                    <span v-for="chip in activeFilterChips" :key="chip.type"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-cyan-50 text-cyan-700 border border-cyan-200">
                        {{ chip.label }}
                        <button @click="removeFilter(chip.type)" class="hover:text-cyan-900 p-1 -mr-1">
                            <X class="w-4 h-4" />
                        </button>
                    </span>
                    <button @click="clearAllFilters" class="text-xs text-cyan-600 hover:underline ml-1">
                        Limpar todos
                    </button>
                </div>

                <!-- Selects colapsáveis -->
                <div v-if="filtersOpen"
                    class="flex flex-col sm:flex-row gap-3 bg-white p-4 rounded-xl border border-gray-200 shadow-sm">

                    <select v-model="selectedPlano"
                        class="block w-full sm:w-56 py-2.5 px-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm transition-shadow cursor-pointer min-h-[44px]">
                        <option v-for="plano in planoOptions" :key="plano.value" :value="plano.value">
                            {{ plano.label }}
                        </option>
                    </select>

                    <select v-model="selectedParceiro"
                        class="block w-full sm:w-64 py-2.5 px-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm transition-shadow cursor-pointer min-h-[44px]">
                        <option v-for="parceiro in parceiroOptions" :key="parceiro.value" :value="parceiro.value">
                            {{ parceiro.label }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- ═══ ERRO SIPROV ═══ -->
            <div v-if="siprovError" class="mt-4 p-6 text-center border rounded-xl bg-white shadow-sm">
                <div class="w-16 h-16 mx-auto bg-amber-50 rounded-full flex items-center justify-center mb-4">
                    <ShieldAlert class="w-8 h-8 text-amber-500" />
                </div>
                <p class="text-lg font-semibold text-gray-900 mb-2">Serviço SIPROV Indisponível</p>
                <p class="text-sm text-gray-500 max-w-md mx-auto mb-4">
                    Não foi possível conectar à API da SIPROV. Verifique sua conexão e tente novamente.
                </p>
                <Button variant="outline" size="sm" @click="navigateTo('siprov.index')">
                    <Activity class="w-4 h-4 mr-1" />
                    Tentar novamente
                </Button>
            </div>

            <template v-if="!siprovError">
                <!-- ═══ BARRA DE STATUS + PAGINAÇÃO SUPERIOR ═══ -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mt-4 mb-3">
                    <span class="text-sm text-gray-500">
                        <span v-if="isLoading" class="inline-block h-4 w-32 rounded bg-gray-200 animate-pulse align-middle" />
                        <template v-else>
                        <span class="font-semibold text-gray-700">{{ filteredAssociados.length }}</span>
                        de {{ allAssociados.length }} associado{{ allAssociados.length !== 1 ? 's' : '' }}
                        </template>
                    </span>

                    <div v-if="!isLoading && totalPages > 1" class="flex items-center gap-2">
                        <Button variant="outline" size="sm" :disabled="currentPage <= 1"
                            @click="goToPage(currentPage - 1)">
                            <ChevronLeft class="w-4 h-4" />
                        </Button>
                        <span class="text-sm text-gray-600 font-medium tabular-nums">
                            Pág. {{ currentPage }} de {{ totalPages }}
                        </span>
                        <Button variant="outline" size="sm" :disabled="currentPage >= totalPages"
                            @click="goToPage(currentPage + 1)">
                            <ChevronRight class="w-4 h-4" />
                        </Button>
                    </div>
                </div>

                <!-- ═══ CARDS (MOBILE) ═══ -->
                <div class="md:hidden space-y-3">
                    <template v-if="isLoading">
                        <div v-for="n in 5" :key="'sk-card-' + n"
                            class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 animate-pulse">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 space-y-2">
                                    <div class="h-4 w-2/3 rounded bg-gray-200" />
                                    <div class="h-3 w-1/3 rounded bg-gray-200" />
                                </div>
                                <div class="h-6 w-16 rounded-full bg-gray-200" />
                            </div>
                            <div class="flex gap-2 mt-3">
                                <div class="h-6 w-24 rounded-full bg-gray-200" />
                                <div class="h-6 w-32 rounded-full bg-gray-200" />
                            </div>
                        </div>
                    </template>

                    <div v-for="item in paginatedAssociados" :key="'card-' + item.codBeneficio"
                        class="bg-white rounded-xl border shadow-sm overflow-hidden transition-all active:bg-gray-50/80 active:scale-[0.98]"
                        :class="item.tenants?.length > 0
                            ? 'border-l-4 border-l-cyan-400'
                            : 'border-l-4 border-l-transparent'">

                        <div @click="toggleCard(item.codPessoa)" class="p-4 cursor-pointer select-none">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold text-gray-900 truncate">
                                        {{ item.nomePessoa }}
                                    </p>
                                    <p class="text-sm text-gray-500 mt-0.5 tabular-nums">
                                        {{ formatCpf(item.cpfCnpj) }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span :class="[
                                        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border',
                                        situacaoClass(item.situacao),
                                    ]">
                                        <span :class="['w-2 h-2 rounded-full', situacaoDot(item.situacao)]" />
                                        {{ item.situacao }}
                                    </span>
                                    <ChevronDown :class="['w-4 h-4 text-gray-400 transition-transform duration-200',
                                        expandedCards.has(item.codPessoa) ? 'rotate-180' : '']" />
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-1.5 mt-2.5">
                                <template v-if="item.tenants?.length > 0">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-cyan-50 text-cyan-700 border border-cyan-200">
                                        <Building2 class="w-3 h-3" />
                                        {{ item.tenants[0]?.details?.[0]?.descricao || item.tenants[0]?.tenant_domain ||
                                            item.tenants[0]?.id }}
                                    </span>
                                </template>
                                <span v-else
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-400 border border-gray-200">
                                    Sem vínculo
                                </span>

                                <span v-for="plano in (item.planos || []).slice(0, 2)" :key="'plano-' + plano.codPlano"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                    <FileText class="w-3 h-3" />
                                    {{ plano.nome }}
                                </span>
                                <span v-if="(item.planos || []).length > 2" class="text-xs text-gray-400">
                                    +{{ item.planos.length - 2 }}
                                </span>
                            </div>
                        </div>

                        <div v-if="expandedCards.has(item.codPessoa)"
                            class="px-4 pb-4 space-y-2.5 border-t border-gray-100 pt-3 bg-gray-50/50">

                            <div v-if="item.email" class="text-xs text-gray-500 break-all">
                                <span class="font-medium text-gray-600">Email:</span>
                                {{ item.email }}
                            </div>
                            <div v-if="item.telefoneCelular" class="text-xs text-gray-500 break-all">
                                <span class="font-medium text-gray-600">Telefone:</span>
                                {{ item.telefoneCelular }}
                            </div>

                            <div class="flex flex-wrap gap-x-6 gap-y-1 text-xs text-gray-500">
                                <span v-if="item.dataAdesao">
                                    <span class="font-medium text-gray-600">Adesão:</span>
                                    {{ formatDate(item.dataAdesao) }}
                                </span>
                                <span v-if="item.dataAtivacao">
                                    <span class="font-medium text-gray-600">Ativação:</span>
                                    {{ formatDate(item.dataAtivacao) }}
                                </span>
                                <span v-if="item.dataCadastro">
                                    <span class="font-medium text-gray-600">Cadastro:</span>
                                    {{ formatDate(item.dataCadastro) }}
                                </span>
                            </div>

                            <div v-if="item.tenants?.length > 1" class="text-xs text-gray-500">
                                <span class="font-medium text-gray-600">+{{ item.tenants.length - 1 }} parceiro(s)
                                    adicional(is)</span>
                            </div>

                            <button @click.stop="openCartaoModal(item)"
                                class="flex items-center gap-2 w-full justify-center mt-2 px-4 py-3 rounded-lg text-sm font-medium text-cyan-700 bg-cyan-50 hover:bg-cyan-100 border border-cyan-200 transition-colors min-h-[44px]">
                                <CreditCard class="w-4 h-4" />
                                Gerar Cartão
                            </button>

                            <button @click.stop="openDependentesModal(item)"
                                class="flex items-center gap-2 w-full justify-center px-4 py-3 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 border border-gray-200 transition-colors min-h-[44px]">
                                <Users class="w-4 h-4" />
                                Dependentes
                            </button>

                            <button v-if="podeAlterarSituacao(item)" @click.stop="openSituacaoModal(item)" :class="[
                                'flex items-center gap-2 w-full justify-center px-4 py-3 rounded-lg text-sm font-medium border transition-colors min-h-[44px]',
                                isAtivo(item)
                                    ? 'text-red-700 bg-red-50 hover:bg-red-100 border-red-200'
                                    : 'text-green-700 bg-green-50 hover:bg-green-100 border-green-200',
                            ]">
                                <component :is="isAtivo(item) ? UserX : UserCheck" class="w-4 h-4" />
                                {{ isAtivo(item) ? 'Inativar' : 'Ativar' }}
                            </button>
                        </div>
                    </div>

                    <div v-if="!hasResults && !isLoading" class="text-center py-16 text-gray-500">
                        <div v-if="hasActiveFilters" class="space-y-3">
                            <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center">
                                <Search class="w-8 h-8 text-gray-400" />
                            </div>
                            <p class="text-lg font-medium text-gray-900">Nenhum resultado</p>
                            <p class="text-sm text-gray-500 max-w-sm mx-auto">
                                Tente buscar por nome ou CPF, ou ajuste os filtros.
                            </p>
                            <Button variant="outline" size="sm" class="mt-2" @click="clearAllFilters">
                                <X class="w-4 h-4 mr-1" />
                                Limpar filtros
                            </Button>
                        </div>
                        <div v-else class="space-y-3">
                            <div class="w-16 h-16 mx-auto bg-cyan-50 rounded-full flex items-center justify-center">
                                <Activity class="w-8 h-8 text-cyan-500" />
                            </div>
                            <p class="text-lg font-medium text-gray-900">Nenhum associado</p>
                            <p class="text-sm text-gray-500">
                                Nenhum associado foi retornado pela SIPROV.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ═══ TABELA (DESKTOP) ═══ -->
                <div class="hidden md:block border rounded-xl border-gray-200 bg-white shadow-sm overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider whitespace-nowrap">
                                    #</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                    Associado</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider whitespace-nowrap">
                                    CPF</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider whitespace-nowrap">
                                    Data de Nascimento</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                    Plano</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider whitespace-nowrap">
                                    Adesão</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider whitespace-nowrap">
                                    Situação</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                    Página de Parceiros</th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500 tracking-wider whitespace-nowrap">
                                    Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <template v-if="isLoading">
                                <tr v-for="n in 8" :key="'sk-row-' + n" class="animate-pulse">
                                    <td class="px-6 py-4"><div class="h-4 w-14 rounded bg-gray-200" /></td>
                                    <td class="px-6 py-4">
                                        <div class="h-4 w-40 rounded bg-gray-200" />
                                        <div class="h-3 w-28 rounded bg-gray-100 mt-2" />
                                    </td>
                                    <td class="px-6 py-4"><div class="h-4 w-28 rounded bg-gray-200" /></td>
                                    <td class="px-6 py-4"><div class="h-4 w-20 rounded bg-gray-200" /></td>
                                    <td class="px-6 py-4"><div class="h-6 w-36 rounded-full bg-gray-200" /></td>
                                    <td class="px-6 py-4"><div class="h-4 w-20 rounded bg-gray-200" /></td>
                                    <td class="px-6 py-4"><div class="h-6 w-16 rounded-full bg-gray-200" /></td>
                                    <td class="px-6 py-4"><div class="h-6 w-24 rounded-full bg-gray-200" /></td>
                                    <td class="px-6 py-4">
                                        <div class="flex justify-end gap-2">
                                            <div class="h-8 w-8 rounded-lg bg-gray-200" />
                                            <div class="h-8 w-8 rounded-lg bg-gray-200" />
                                            <div class="h-8 w-8 rounded-lg bg-gray-200" />
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr v-for="(item, idx) in paginatedAssociados" :key="'row-' + item.codPessoa + '-' + idx"
                                class="even:bg-gray-50/50 hover:bg-cyan-50/30 transition-colors group" :class="item.tenants?.length > 0
                                    ? 'border-l-4 border-l-cyan-400'
                                    : 'border-l-4 border-l-transparent'">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 font-mono tabular-nums">
                                    {{ item.codPessoa }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div
                                        class="font-semibold text-gray-900 group-hover:text-cyan-700 transition-colors">
                                        {{ item.nomePessoa }}
                                    </div>
                                    <div v-if="item.email" class="text-xs text-gray-400 truncate max-w-xs mt-0.5">
                                        {{ item.email }}
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700 tabular-nums">
                                    {{ formatCpf(item.cpfCnpj) }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 tabular-nums">
                                    {{ formatDate(item.dataNascimento) || '—' }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="flex flex-wrap gap-1">
                                        <span v-for="(plano, pIdx) in item.planos" :key="pIdx"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                            <FileText class="w-3 h-3" />
                                            {{ plano.nome }}
                                        </span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 tabular-nums">
                                    {{ formatDate(item.dataAdesao) || '—' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <span :class="[
                                        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border',
                                        situacaoClass(item.situacao),
                                    ]">
                                        <span :class="['w-2 h-2 rounded-full', situacaoDot(item.situacao)]" />
                                        {{ item.situacao }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <template v-if="item.tenants && item.tenants.length > 0">
                                        <div v-for="(tenant, tIdx) in item.tenants" :key="tIdx"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-cyan-50 text-cyan-700 border border-cyan-200 mr-1 mb-1">
                                            <Building2 class="w-3 h-3" />
                                            <a v-if="tenant.tenant_domain" :href="tenant.url" target="_blank"
                                                class="hover:underline inline-flex items-center gap-1">
                                                {{ tenant.details?.[0]?.descricao || tenant.tenant_domain || tenant.id
                                                }}
                                                <ExternalLink class="w-3 h-3 opacity-50" />
                                            </a>
                                            <span v-else>
                                                {{ tenant.details?.[0]?.descricao || tenant.tenant_domain || tenant.id
                                                }}
                                            </span>
                                        </div>
                                    </template>
                                    <span v-else
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-400 border border-gray-200">
                                        —
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <button @click="openCartaoModal(item)"
                                        class="p-2.5 text-cyan-600 hover:text-cyan-800 hover:bg-cyan-50 rounded-lg transition-all"
                                        title="Gerar Cartão">
                                        <CreditCard class="w-5 h-5" />
                                    </button>
                                    <button @click="openDependentesModal(item)"
                                        class="p-2.5 text-gray-500 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-all"
                                        title="Dependentes">
                                        <Users class="w-5 h-5" />
                                    </button>
                                    <button v-if="podeAlterarSituacao(item)" @click="openSituacaoModal(item)" :class="[
                                        'p-2.5 rounded-lg transition-all',
                                        isAtivo(item)
                                            ? 'text-red-500 hover:text-red-700 hover:bg-red-50'
                                            : 'text-green-600 hover:text-green-800 hover:bg-green-50',
                                    ]" :title="isAtivo(item) ? 'Inativar' : 'Ativar'">
                                        <component :is="isAtivo(item) ? UserX : UserCheck" class="w-5 h-5" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-if="!hasResults && !isLoading" class="text-center py-16 text-gray-500">
                        <div v-if="hasActiveFilters" class="space-y-3">
                            <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center">
                                <Search class="w-8 h-8 text-gray-400" />
                            </div>
                            <p class="text-lg font-medium text-gray-900">Nenhum resultado</p>
                            <p class="text-sm text-gray-500 max-w-sm mx-auto">
                                Tente buscar por nome ou CPF, ou ajuste os filtros.
                            </p>
                            <Button variant="outline" size="sm" class="mt-2" @click="clearAllFilters">
                                <X class="w-4 h-4 mr-1" />
                                Limpar filtros
                            </Button>
                        </div>
                        <div v-else class="space-y-3">
                            <div class="w-16 h-16 mx-auto bg-cyan-50 rounded-full flex items-center justify-center">
                                <Activity class="w-8 h-8 text-cyan-500" />
                            </div>
                            <p class="text-lg font-medium text-gray-900">Nenhum associado</p>
                            <p class="text-sm text-gray-500">
                                Nenhum associado foi retornado pela SIPROV.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ═══ PAGINAÇÃO INFERIOR ═══ -->
                <div v-if="!isLoading && hasResults"
                    class="flex flex-col sm:flex-row items-center justify-between gap-3 mt-4">
                    <div class="text-sm text-gray-500 tabular-nums">
                        Mostrando {{ pageRange.start }}–{{ pageRange.end }} de {{ filteredAssociados.length }}
                        associado{{ filteredAssociados.length !== 1 ? 's' : '' }}
                    </div>
                    <nav v-if="totalPages > 1" class="flex items-center gap-1" aria-label="Paginação">
                        <Button variant="outline" size="sm" :disabled="currentPage <= 1"
                            @click="goToPage(currentPage - 1)">
                            <ChevronLeft class="w-4 h-4" />
                            <span class="hidden sm:inline ml-1">Anterior</span>
                        </Button>
                        <template v-for="(p, i) in pageNumbers" :key="'p-' + i">
                            <span v-if="p === '…'" class="px-2 text-sm text-gray-400">…</span>
                            <button v-else type="button" @click="goToPage(p)" :aria-current="p === currentPage ? 'page' : undefined"
                                :class="[
                                    'min-w-[36px] h-9 px-2 rounded-lg text-sm font-medium tabular-nums transition-colors',
                                    p === currentPage
                                        ? 'bg-cyan-500 text-white shadow-sm'
                                        : 'text-gray-600 hover:bg-gray-100',
                                ]">
                                {{ p }}
                            </button>
                        </template>
                        <Button variant="outline" size="sm" :disabled="currentPage >= totalPages"
                            @click="goToPage(currentPage + 1)">
                            <span class="hidden sm:inline mr-1">Próxima</span>
                            <ChevronRight class="w-4 h-4" />
                        </Button>
                    </nav>
                </div>
            </template>
        </div>
    </CentralAdminLayout>

    <ConfirmDeleteModal :show="cartaoModal.show" title="Gerar Cartão de Benefício"
        :message="'Deseja gerar o cartão de benefício para ' + (cartaoModal.item?.nomePessoa || 'este associado') + '?'"
        warning-message="O cartão será baixado automaticamente como arquivo PDF." confirm-text="Sim, Gerar"
        cancel-text="Cancelar" :is-processing="cartaoModal.isProcessing" variant="info" @close="closeCartaoModal"
        @confirm="confirmGerarCartao" />

    <ConfirmDeleteModal :show="situacaoModal.show"
        :title="situacaoModal.ativar ? 'Ativar Associado' : 'Inativar Associado'"
        :message="(situacaoModal.ativar ? 'Deseja ativar ' : 'Deseja inativar ') + (situacaoModal.item?.nomePessoa || 'este associado') + '?'"
        :warning-message="'O benefício será gravado como ' + (situacaoModal.ativar ? 'ATIVO' : 'INATIVO') + ' na SIPROV.'"
        :confirm-text="situacaoModal.ativar ? 'Sim, Ativar' : 'Sim, Inativar'"
        cancel-text="Cancelar" :is-processing="situacaoModal.isProcessing"
        :variant="situacaoModal.ativar ? 'info' : 'danger'"
        @close="closeSituacaoModal" @confirm="confirmAlterarSituacao" />

    <Teleport to="body">
        <div v-if="dependentesModal.show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeDependentesModal" />

            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
                <div class="flex items-start justify-between gap-4 p-6 pb-4 border-b border-gray-100">
                    <div class="min-w-0">
                        <h3 class="text-lg font-semibold text-gray-900">Dependentes</h3>
                        <p class="text-sm text-gray-500 truncate">{{ dependentesModal.item?.nomePessoa }}</p>
                    </div>
                    <button @click="closeDependentesModal" class="p-1 text-gray-400 hover:text-gray-600">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-6 max-h-[60vh] overflow-y-auto">
                    <div v-if="dependentesModal.loading" class="flex justify-center py-8">
                        <Loader2 class="w-6 h-6 text-cyan-500 animate-spin" />
                    </div>

                    <p v-else-if="dependentesModal.error" class="text-sm text-red-600 text-center py-6">
                        {{ dependentesModal.error }}
                    </p>

                    <p v-else-if="!dependentesModal.dependentes.length" class="text-sm text-gray-500 text-center py-6">
                        Nenhum dependente vinculado a este benefício.
                    </p>

                    <ul v-else class="divide-y divide-gray-100">
                        <li v-for="dep in dependentesModal.dependentes" :key="dep.codDependente"
                            class="flex items-center justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <p class="font-medium text-gray-900 truncate">{{ dep.nome }}</p>
                                <p class="text-xs text-gray-500 tabular-nums">
                                    {{ dep.parentesco || '—' }} · {{ formatCpf(dep.cpf) }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <span :class="[
                                    'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border',
                                    situacaoClass(dep.ativo ? 'ativo' : 'inativo'),
                                ]">
                                    <span :class="['w-2 h-2 rounded-full', situacaoDot(dep.ativo ? 'ativo' : 'inativo')]" />
                                    {{ dep.ativo ? 'Ativo' : 'Inativo' }}
                                </span>

                                <template v-if="can.delete">
                                    <template v-if="dependentesModal.confirmingCod === dep.codDependente">
                                        <button @click="dependentesModal.confirmingCod = null"
                                            :disabled="dependentesModal.processingCod === dep.codDependente"
                                            class="px-2.5 py-1 rounded-lg text-xs font-medium text-gray-600 hover:bg-gray-100">
                                            Cancelar
                                        </button>
                                        <button @click="alterarSituacaoDependente(dep)"
                                            :disabled="dependentesModal.processingCod === dep.codDependente"
                                            :class="[
                                                'inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium text-white disabled:opacity-60',
                                                dep.ativo ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700',
                                            ]">
                                            <Loader2 v-if="dependentesModal.processingCod === dep.codDependente"
                                                class="w-3 h-3 animate-spin" />
                                            Confirmar
                                        </button>
                                    </template>
                                    <button v-else @click="dependentesModal.confirmingCod = dep.codDependente"
                                        :disabled="!!dependentesModal.processingCod"
                                        :class="[
                                            'inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium border',
                                            dep.ativo
                                                ? 'text-red-700 bg-red-50 hover:bg-red-100 border-red-200'
                                                : 'text-green-700 bg-green-50 hover:bg-green-100 border-green-200',
                                        ]">
                                        <component :is="dep.ativo ? UserX : UserCheck" class="w-3.5 h-3.5" />
                                        {{ dep.ativo ? 'Inativar' : 'Ativar' }}
                                    </button>
                                </template>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </Teleport>
</template>
