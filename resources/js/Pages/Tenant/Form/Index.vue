<script setup>
import { ref, watch, computed } from "vue";
import { Head, router } from "@inertiajs/vue3";
import {
    CalendarClock, CheckCircle2, Copy, ExternalLink, Eye, FileText, Info, Lock, Search, Star, X,
} from "lucide-vue-next";
import { Button } from "@/Components/ui/button";
import TenantLayout from "@/Layouts/TenantAdminLayout.vue";
import PaginationSimple from "@/Components/PaginationSimple.vue";
import { showToast } from '@/Utils/toast';
import CountdownBadge from "@/Components/CountdownBadge.vue";

const props = defineProps({
    tenant: {
        type: Object,
        required: true,
        default: () => ({ id: '', name: '' })
    },
    tenantDetails: {
        type: Object,
        default: null
    },
    tenantForms: {
        type: Object,
        required: true,
        default: () => ({
            data: [],
            links: [],
            from: 0,
            to: 0,
            total: 0
        })
    },
    stats: {
        type: Object,
        default: () => ({ total: 0, disponiveis: 0, indisponiveis: 0 })
    },
    filters: {
        type: Object,
        default: () => ({ search: '', disponibilidade: '' })
    },
});

const search = ref(props.filters.search || '');
const disponibilidade = ref(props.filters.disponibilidade || '');
let searchTimeout = null;

const STATUS_CONFIG = {
    ativo: { label: 'Ativo', classes: 'bg-green-100 text-green-700' },
    rascunho: { label: 'Em edição', classes: 'bg-gray-100 text-gray-600' },
    pausado: { label: 'Pausado', classes: 'bg-yellow-100 text-yellow-700' },
    encerrado: { label: 'Encerrado', classes: 'bg-red-100 text-red-600' },
};
const getStatusConfig = (status) => STATUS_CONFIG[status] || { label: status || '—', classes: 'bg-gray-100 text-gray-600' };

// Explica ao usuário por que o formulário não pode ser preenchido
const motivoIndisponivel = (tenantForm) => {
    if (tenantForm.expirado) return 'O prazo deste formulário terminou.';
    const motivos = {
        rascunho: 'Este formulário ainda está em edição.',
        pausado: 'Este formulário está pausado no momento.',
        encerrado: 'Este formulário foi encerrado.',
    };
    return motivos[tenantForm.form?.status] || 'Este formulário não está ativo.';
};

const tabs = computed(() => [
    { value: '', label: 'Todos', count: props.stats.total },
    { value: 'disponiveis', label: 'Disponíveis', count: props.stats.disponiveis },
    { value: 'indisponiveis', label: 'Indisponíveis', count: props.stats.indisponiveis },
]);

const publicUrl = (form) => route("forms.public.show", form.slug);

const fillForm = (tenantForm) => {
    if (!tenantForm.pode_preencher) {
        showToast(motivoIndisponivel(tenantForm), 'warning');
        return;
    }
    window.open(publicUrl(tenantForm.form), '_blank', 'noopener,noreferrer');
};

const copyLink = async (tenantForm) => {
    try {
        await navigator.clipboard.writeText(publicUrl(tenantForm.form));
        showToast('Link copiado para a área de transferência', 'success');
    } catch {
        showToast('Não foi possível copiar o link', 'error');
    }
};

const viewForm = (tenantForm) => router.visit(route('meus-formularios.show', tenantForm.form.id));

const applyFilters = () => {
    router.get(
        route('meus-formularios.index'),
        {
            search: search.value || undefined,
            disponibilidade: disponibilidade.value || undefined,
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
    searchTimeout = setTimeout(applyFilters, 350);
});
watch(disponibilidade, applyFilters);

const clearFilters = () => {
    clearTimeout(searchTimeout);
    search.value = '';
    disponibilidade.value = '';
};

const hasFilters = computed(() => Boolean(search.value || disponibilidade.value));
const tenantPrimaryColor = computed(() => props.tenantDetails?.cor_primaria || '#0891b2');
</script>

<template>
    <Head title="Meus Formulários" />
    <TenantLayout :tenant-details="tenantDetails">
        <!-- Cabeçalho -->
        <div class="mb-5">
            <h1 class="text-xl sm:text-2xl font-bold" :style="{ color: tenantPrimaryColor }">
                Meus Formulários
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Preencha, visualize e compartilhe os formulários disponíveis para você.
            </p>
        </div>

        <!-- Aviso -->
        <div class="flex items-start gap-3 rounded-lg border border-blue-200 bg-blue-50 p-4 mb-5">
            <Info class="h-5 w-5 flex-shrink-0 text-blue-600 mt-0.5" />
            <p class="text-sm text-blue-900">
                <span class="font-semibold">Importante:</span>
                apenas formulários
                <span class="inline-flex items-center gap-1 rounded bg-green-100 px-1.5 py-0.5 font-semibold text-green-700">
                    <CheckCircle2 class="h-3.5 w-3.5" /> ATIVOS
                </span>
                podem ser preenchidos. Os demais ficam disponíveis apenas para visualização.
            </p>
        </div>

        <!-- Filtros -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
            <div class="inline-flex w-full sm:w-auto rounded-lg border border-gray-200 bg-white p-1 shadow-sm"
                role="tablist" aria-label="Filtrar por disponibilidade">
                <button v-for="tab in tabs" :key="tab.value" type="button" role="tab"
                    :aria-selected="disponibilidade === tab.value"
                    class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                    :class="disponibilidade === tab.value ? 'text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                    :style="disponibilidade === tab.value ? { backgroundColor: tenantPrimaryColor } : {}"
                    @click="disponibilidade = tab.value">
                    {{ tab.label }}
                    <span class="rounded-full px-1.5 text-xs tabular-nums"
                        :class="disponibilidade === tab.value ? 'bg-white/25' : 'bg-gray-100 text-gray-500'">
                        {{ tab.count }}
                    </span>
                </button>
            </div>

            <div class="relative w-full sm:w-72">
                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                <input v-model="search" type="search" placeholder="Buscar por título ou descrição..."
                    aria-label="Buscar formulários"
                    class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-9 pr-9 text-sm shadow-sm focus:outline-none focus:ring-2"
                    :style="{ '--tw-ring-color': tenantPrimaryColor }" />
                <button v-if="search" type="button" aria-label="Limpar busca"
                    class="absolute right-2 top-1/2 -translate-y-1/2 rounded p-1 text-gray-400 hover:text-gray-600"
                    @click="search = ''">
                    <X class="h-4 w-4" />
                </button>
            </div>
        </div>

        <!-- Lista de formulários -->
        <div v-if="tenantForms.data?.length" class="space-y-3">
            <article v-for="tenantForm in tenantForms.data" :key="tenantForm.id"
                class="relative overflow-hidden rounded-lg border border-gray-200 shadow-sm transition-shadow hover:shadow-md"
                :class="tenantForm.pode_preencher ? 'bg-white' : 'bg-gray-50'">
                <!-- Faixa lateral indicando disponibilidade -->
                <span class="absolute inset-y-0 left-0 w-1"
                    :style="{ backgroundColor: tenantForm.pode_preencher ? tenantPrimaryColor : '#d1d5db' }" />

                <div class="flex flex-col gap-4 p-4 pl-5 lg:flex-row lg:items-center">
                    <!-- Informações -->
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-base font-semibold"
                                :class="tenantForm.pode_preencher ? 'text-gray-900' : 'text-gray-600'">
                                {{ tenantForm.form.title || 'Sem título' }}
                            </h2>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="tenantForm.expirado ? 'bg-red-100 text-red-600' : getStatusConfig(tenantForm.form.status).classes">
                                {{ tenantForm.expirado ? 'Expirado' : getStatusConfig(tenantForm.form.status).label }}
                            </span>
                            <span v-if="tenantForm.principal"
                                class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800">
                                <Star class="h-3 w-3" /> Principal
                            </span>
                        </div>

                        <p v-if="tenantForm.form.description" class="mt-1 text-sm text-gray-500 line-clamp-2">
                            {{ tenantForm.form.description }}
                        </p>

                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500">
                            <span class="inline-flex items-center gap-1.5">
                                <CalendarClock class="h-3.5 w-3.5" />
                                <template v-if="tenantForm.form.expires_at_br">
                                    Expira em {{ tenantForm.form.expires_at_br }}
                                </template>
                                <template v-else>Sem data de expiração</template>
                            </span>
                            <CountdownBadge v-if="tenantForm.form.expires_at_br && tenantForm.pode_preencher"
                                :current-date="tenantForm.form.atual_at_br"
                                :expire-date="tenantForm.form.expires_at_br" />
                            <span v-if="tenantForm.origem">Origem: {{ tenantForm.origem }}</span>
                        </div>

                        <p v-if="!tenantForm.pode_preencher"
                            class="mt-2 inline-flex items-center gap-1.5 text-xs font-medium text-gray-500">
                            <Lock class="h-3.5 w-3.5" />
                            {{ motivoIndisponivel(tenantForm) }} Disponível apenas para visualização.
                        </p>
                    </div>

                    <!-- Ações -->
                    <div class="flex flex-wrap items-center gap-2 lg:flex-nowrap lg:justify-end">
                        <template v-if="tenantForm.pode_preencher">
                            <Button size="sm" class="flex-1 sm:flex-none"
                                :style="{ backgroundColor: tenantPrimaryColor }" @click="fillForm(tenantForm)">
                                <ExternalLink /> Preencher
                            </Button>
                            <Button variant="outline" size="sm" class="flex-1 sm:flex-none"
                                title="Copiar link público" @click="copyLink(tenantForm)">
                                <Copy /> Copiar link
                            </Button>
                        </template>
                        <Button variant="outline" size="sm" class="flex-1 sm:flex-none" @click="viewForm(tenantForm)">
                            <Eye /> Visualizar
                        </Button>
                    </div>
                </div>
            </article>
        </div>

        <!-- Estado vazio -->
        <div v-else class="rounded-lg border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
            <FileText class="mx-auto h-10 w-10 text-gray-300" />
            <p class="mt-3 text-sm font-medium text-gray-700">
                {{ hasFilters ? 'Nenhum formulário encontrado com esses filtros.' : 'Você ainda não tem formulários.' }}
            </p>
            <p v-if="!hasFilters" class="mt-1 text-sm text-gray-500">
                Quando um formulário for vinculado a você, ele aparecerá aqui.
            </p>
            <Button v-if="hasFilters" variant="outline" size="sm" class="mt-4" @click="clearFilters">
                Limpar filtros
            </Button>
        </div>

        <div v-if="tenantForms.data?.length" class="mt-4 rounded-lg border border-gray-200 bg-white shadow-sm">
            <PaginationSimple :data="tenantForms" :links="tenantForms.links" :has-data="true" label="formulários" />
        </div>
    </TenantLayout>
</template>
