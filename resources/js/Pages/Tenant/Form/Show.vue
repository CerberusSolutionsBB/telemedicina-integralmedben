<script setup>
import { ref, computed } from "vue";
import { Head, router, Link } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import TenantAdminLayout from "@/Layouts/TenantAdminLayout.vue";
import { showToast } from '@/Utils/toast';
import FormField from "@/Components/FormFields/FormField.vue";
import CreatePatientFromResponseModal from "@/Components/CreatePatientFromResponseModal.vue";
import {
    ArrowLeft,
    Edit,
    Trash2,
    Download,
    Share2,
    Users,
    Calendar,
    Globe,
    Lock,
    BarChart3,
    MessageSquare,
    CheckCircle2,
    Clock,
    Eye,
    ChevronLeft,
    ChevronRight,
    Home,
    Loader2,
    Check,
    UserPlus,
    FileText,
    Power,
    PowerOff,
} from "lucide-vue-next";
const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    stats: {
        type: Object,
        required: true
    },
    fieldStats: {
        type: Object,
        default: () => ({})
    },
    responses: {
        type: Object,
        required: true
    },
    can: {
        type: Object,
        default: () => ({ edit: false, delete: false, export: false })
    },
    shareUrl: {
        type: String,
        default: null
    }
});
const activeTab = ref('overview');
const loading = ref(false);
const linkCopied = ref(false);
const patientDialogOpen = ref(false);
const selectedResponse = ref(null);
const statusColors = {
    rascunho: 'bg-gray-100 text-gray-800 border-gray-200',
    ativo: 'bg-green-100 text-green-800 border-green-200',
    pausado: 'bg-yellow-100 text-yellow-800 border-yellow-200',
    encerrado: 'bg-red-100 text-red-800 border-red-200'
};
const statusLabels = {
    rascunho: 'Rascunho',
    ativo: 'Ativo',
    pausado: 'Pausado',
    encerrado: 'Encerrado'
};
const breadcrumbs = computed(() => [
    { label: 'Início', href: route('dashboard'), icon: Home },
    { label: 'Formulários', href: route('forms.index') },
    { label: props.form.title, href: null }
]);
const maxDailyResponse = computed(() => {
    if (!props.stats.daily_responses?.length) return 1;
    return Math.max(...props.stats.daily_responses.map(d => d.count));
});
const formattedStats = computed(() => ({
    completionRate: props.form.response_limit
        ? Math.round((props.stats.total_responses / props.form.response_limit) * 100) + '%'
        : 'Ilimitado',
    lastResponse: props.stats.last_response_at
        ? formatDate(props.stats.last_response_at)
        : 'Nenhuma resposta',
    totalFields: props.form.fields?.length || 0,
    hasResponses: props.stats.total_responses > 0
}));
const lawInfo = computed(() => {
    if (!props.form.lei) return null;
    return {
        title: props.form.lei.title,
        type: props.form.lei.type?.toUpperCase(),
        excerpt: stripHtml(props.form.lei.text).substring(0, 100) + '...'
    };
});
const hasValidFields = computed(() =>
    Array.isArray(props.form.fields) && props.form.fields.length > 0
);
const calculateWidth = (count) => {
    const max = maxDailyResponse.value || 1;
    return Math.min((count / max) * 100, 100);
};
const stripHtml = (html) => {
    if (!html) return '';
    return html.replace(/<[^>]*>/g, '');
};
const copyShareLink = async () => {
    if (!props.shareUrl) return;
    try {
        await navigator.clipboard.writeText(props.shareUrl);
        linkCopied.value = true;
        showToast('Link copiado para área de transferência!', 'success');
        setTimeout(() => linkCopied.value = false, 2000);
    } catch (err) {
        showToast('Erro ao copiar link. Tente manualmente.', 'error');
    }
};
const deleteForm = async () => {
    if (!confirm('⚠️ Tem certeza que deseja excluir este formulário?\n\nEsta ação não pode ser desfeita e todas as respostas serão perdidas.')) {
        return;
    }
    loading.value = true;
    try {
        await router.delete(route('forms.destroy', props.form.id), {
            preserveScroll: true,
            onSuccess: () => {
                showToast('Formulário excluído com sucesso!', 'success');
            },
            onError: (errors) => {
                const message = errors?.message || 'Erro ao excluir formulário. Tente novamente.';
                showToast(message, 'error');
            },
            onFinish: () => {
                loading.value = false;
            }
        });
    } catch (error) {
        loading.value = false;
        showToast('Erro inesperado ao excluir', 'error');
        console.error('Delete error:', error);
    }
};
const handlePagination = (url) => {
    if (!url) return;
    loading.value = true;
    router.get(url, {}, {
        preserveState: true,
        preserveScroll: true,
        only: ['responses'],
        onFinish: () => {
            loading.value = false;
        }
    });
};
const openPatientDialog = (response) => {
    selectedResponse.value = response;
    patientDialogOpen.value = true;
};
const formatAnswer = (answer, fieldType) => {
    if (answer === null || answer === undefined || answer === '') {
        return '-';
    }
    if (Array.isArray(answer)) {
        return answer.join(', ');
    }
    if (fieldType === 'checkbox' && typeof answer === 'object' && answer !== null) {
        return Object.entries(answer)
            .filter(([_, v]) => v)
            .map(([k, _]) => k)
            .join(', ');
    }
    return answer;
};
const formatDate = (dateString) => {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '-';
        return date.toLocaleDateString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    } catch {
        return '-';
    }
};
const formatDateTime = (dateString) => {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '-';
        return date.toLocaleString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch {
        return '-';
    }
};
</script>
<template>

    <Head :title="props.form.title" />
    <TenantAdminLayout>
        <!-- ==========================================
             BREADCRUMB
             ========================================== -->
        <nav class="flex items-center gap-2 text-sm text-gray-500 mb-4" aria-label="Breadcrumb">
            <Link :href="route('meus-formularios.index')" class="hover:text-cyan-600 transition-colors">
                Formulários
            </Link>
            <ChevronRight class="w-4 h-4" aria-hidden="true" />
            <span class="text-gray-900 font-medium truncate max-w-xs" aria-current="page">
                {{ props.form.title }}
            </span>
        </nav>
        <!-- ==========================================
             HEADER
             ========================================== -->
        <header class="mb-6">
            <!-- Debug removido em produção -->
            <!-- <pre>{{ props.form }}</pre> -->
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <!-- Título e Status -->
                    <div class="flex items-center gap-3 mb-2 flex-wrap">
                        <h1 class="text-2xl font-bold text-gray-900 truncate">
                            {{ props.form.title }}
                        </h1>
                        <span
                            :class="['px-2.5 py-0.5 rounded-full text-xs font-medium border', statusColors[props.form.status]]">
                            {{ statusLabels[props.form.status] }}
                        </span>
                    </div>
                    <!-- Descrição -->
                    <p class="text-gray-600 max-w-2xl line-clamp-2">
                        {{ props.form.description || 'Sem descrição disponível' }}
                    </p>
                    <!-- Metadados -->
                    <div class="flex flex-wrap items-center gap-4 mt-3 text-sm text-gray-500">
                        <span class="flex items-center gap-1" title="Criado por">
                            <Users class="w-4 h-4" aria-hidden="true" />
                            {{ props.form.created_by }}
                        </span>
                        <span v-if="props.form.published_at" class="flex items-center gap-1" title="Data de publicação">
                            <Calendar class="w-4 h-4" aria-hidden="true" />
                            Publicado em {{ formatDate(props.form.published_at) }}
                        </span>
                        <span class="flex items-center gap-1" :class="props.form.is_public ? 'text-green-600' : ''"
                            title="Visibilidade do formulário">
                            <Globe v-if="props.form.is_public" class="w-4 h-4" aria-hidden="true" />
                            <Lock v-else class="w-4 h-4" aria-hidden="true" />
                            {{ props.form.is_public ? 'Público' : 'Privado' }}
                        </span>
                        <span v-if="props.form.code" class="text-xs text-gray-400 font-mono"
                            title="Código único do formulário">
                            Código: {{ props.form.code.substring(0, 8) }}...
                        </span>
                    </div>
                    <!-- Badge da Lei LGPD (se existir) -->
                    <div v-if="lawInfo" class="mt-3">
                        <span
                            class="inline-flex items-center gap-1 px-2 py-1 rounded bg-purple-50 text-purple-700 text-xs font-medium border border-purple-200">
                            <Lock class="w-3 h-3" />
                            {{ lawInfo.type }}: {{ lawInfo.title }}
                        </span>
                    </div>
                </div>
                <!-- Ações -->
                <div class="flex flex-wrap gap-2">
                    <Button v-if="shareUrl" variant="outline" @click="copyShareLink" class="gap-2"
                        :disabled="linkCopied">
                        <Check v-if="linkCopied" class="w-4 h-4 text-green-500" />
                        <Share2 v-else class="w-4 h-4" />
                        {{ linkCopied ? 'Copiado!' : 'Copiar Link' }}
                    </Button>
                    <Link v-if="can.edit" :href="route('forms.edit', props.form.id)">
                        <Button variant="outline" class="gap-2">
                            <Edit class="w-4 h-4" /> Editar
                        </Button>
                    </Link>
                    <Button v-if="can.delete" variant="destructive" @click="deleteForm" class="gap-2"
                        :disabled="loading">
                        <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
                        <Trash2 v-else class="w-4 h-4" />
                        Excluir
                    </Button>
                </div>
            </div>
        </header>
        <!-- ==========================================
             TABS NAVEGAÇÃO
             ========================================== -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8 overflow-x-auto" role="tablist" aria-label="Navegação das abas">
                <button @click="activeTab = 'overview'" :class="['py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors whitespace-nowrap',
                    activeTab === 'overview'
                        ? 'border-cyan-500 text-cyan-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                ]" role="tab" :aria-selected="activeTab === 'overview'" aria-controls="overview-panel"
                    id="tab-overview">
                    <BarChart3 class="w-4 h-4" aria-hidden="true" />
                    Visão Geral
                </button>
                <button @click="activeTab = 'responses'" :class="['py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors whitespace-nowrap',
                    activeTab === 'responses'
                        ? 'border-cyan-500 text-cyan-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                ]" role="tab" :aria-selected="activeTab === 'responses'" aria-controls="responses-panel"
                    id="tab-responses">
                    <MessageSquare class="w-4 h-4" aria-hidden="true" />
                    Respostas
                    <span v-if="props.form.responses_count > 0"
                        class="ml-1 px-2 py-0.5 rounded-full text-xs bg-cyan-100 text-cyan-700 font-semibold">
                        {{ props.form.responses_count }}
                    </span>
                </button>
                <button @click="activeTab = 'analytics'" :class="['py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors whitespace-nowrap',
                    activeTab === 'analytics'
                        ? 'border-cyan-500 text-cyan-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                ]" role="tab" :aria-selected="activeTab === 'analytics'" aria-controls="analytics-panel"
                    id="tab-analytics">
                    <Eye class="w-4 h-4" aria-hidden="true" />
                    Análise por Campo
                </button>
            </nav>
        </div>
        <!-- ==========================================
             TAB: VISÃO GERAL
             ========================================== -->
        <div v-show="activeTab === 'overview'" id="overview-panel" role="tabpanel" aria-labelledby="tab-overview"
            class="space-y-6">
            <!-- Cards de Estatísticas -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total de Respostas -->
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total de Respostas</p>
                            <p class="text-3xl font-bold text-gray-900 mt-1">
                                {{ props.stats.total_responses || 0 }}
                            </p>
                        </div>
                        <div class="p-3 bg-cyan-50 rounded-lg">
                            <MessageSquare class="w-6 h-6 text-cyan-600" aria-hidden="true" />
                        </div>
                    </div>
                </div>
                <!-- Taxa de Resposta -->
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Taxa de Resposta</p>
                            <p class="text-3xl font-bold text-gray-900 mt-1">
                                {{ formattedStats.completionRate }}
                            </p>
                        </div>
                        <div class="p-3 bg-green-50 rounded-lg">
                            <CheckCircle2 class="w-6 h-6 text-green-600" aria-hidden="true" />
                        </div>
                    </div>
                    <p v-if="props.form.response_limit" class="text-xs text-gray-500 mt-2">
                        Limite: {{ props.form.response_limit }} respostas
                    </p>
                </div>
                <!-- Última Resposta -->
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Última Resposta</p>
                            <p class="text-lg font-bold text-gray-900 mt-1">
                                {{ formattedStats.lastResponse }}
                            </p>
                        </div>
                        <div class="p-3 bg-blue-50 rounded-lg">
                            <Clock class="w-6 h-6 text-blue-600" aria-hidden="true" />
                        </div>
                    </div>
                </div>
                <!-- Status -->
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Status</p>
                            <p class="text-lg font-bold mt-1" :class="statusColors[props.form.status]">
                                {{ statusLabels[props.form.status] }}
                            </p>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <Globe class="w-6 h-6 text-gray-600" aria-hidden="true" />
                        </div>
                    </div>
                </div>
            </div>
            <!-- Gráfico de Respostas por Dia -->
            <div v-if="props.stats.daily_responses?.length > 0"
                class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Respostas nos Últimos Dias</h3>
                    <span class="text-sm text-gray-500">
                        {{ props.stats.daily_responses.length }} dias
                    </span>
                </div>
                <div class="space-y-3">
                    <div v-for="day in props.stats.daily_responses.slice(0, 10)" :key="day.date"
                        class="flex items-center gap-4 group">
                        <div class="w-24 text-sm text-gray-600 font-medium">
                            {{ formatDate(day.date) }}
                        </div>
                        <div class="flex-1 bg-gray-100 rounded-full h-6 overflow-hidden relative">
                            <div class="h-full bg-gradient-to-r from-cyan-500 to-cyan-600 rounded-full transition-all duration-500 ease-out group-hover:from-cyan-400"
                                :style="{ width: calculateWidth(day.count) + '%' }"></div>
                        </div>
                        <div class="w-12 text-sm font-bold text-gray-900 text-right">
                            {{ day.count }}
                        </div>
                    </div>
                </div>
            </div>
            <!-- Estrutura do Formulário -->
            <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Estrutura do Formulário
                    </h3>
                    <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-600 font-medium">
                        {{ formattedStats.totalFields }} campos
                    </span>
                </div>
                <div v-if="!hasValidFields" class="p-8 text-center text-gray-500">
                    <p>Este formulário não possui campos configurados.</p>
                    <Link v-if="can.edit" :href="route('forms.edit', props.form.id)"
                        class="text-cyan-600 hover:text-cyan-700 text-sm mt-2 inline-block">
                        Adicionar campos →
                    </Link>
                </div>
                <div v-else class="space-y-4">
                    <div v-for="(field, index) in props.form.fields" :key="field.id"
                        class="p-4 bg-gray-50 rounded-lg border border-gray-200 hover:border-cyan-300 transition-colors">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-gray-400 font-mono">#{{ index + 1 }}</span>
                                    <p class="font-medium text-gray-900 truncate">
                                        {{ field.label || 'Campo sem título' }}
                                        <span v-if="field.required" class="text-red-500" title="Obrigatório">*</span>
                                    </p>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">
                                    Tipo: <span class="font-medium capitalize">{{ field.type }}</span>
                                    <span v-if="field.options?.length">| {{ field.options.length }} opções</span>
                                </p>
                                <p v-if="field.help_text" class="text-sm text-gray-600 mt-1">
                                    {{ field.help_text }}
                                </p>
                            </div>
                            <span
                                class="px-2 py-1 rounded border text-xs border-gray-300 text-gray-600 bg-white capitalize">
                                {{ field.type }}
                            </span>
                        </div>
                        <!-- Preview do campo -->
                        <FormField :field="field" disabled class="bg-white" />
                    </div>
                </div>
            </div>
        </div>
        <!-- ==========================================
             TAB: RESPOSTAS
             ========================================== -->
        <div v-show="activeTab === 'responses'" id="responses-panel" role="tabpanel" aria-labelledby="tab-responses"
            class="space-y-6">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <!-- Header -->
                <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Respostas Individuais</h3>
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-500">
                            {{ props.responses.total || 0 }} total
                        </span>
                        <Loader2 v-if="loading" class="w-4 h-4 animate-spin text-cyan-600" />
                    </div>
                </div>
                <!-- Loading State -->
                <div v-if="loading && !props.responses.data?.length" class="p-8 text-center">
                    <Loader2 class="w-8 h-8 animate-spin text-cyan-600 mx-auto mb-3" />
                    <p class="text-gray-500">Carregando respostas...</p>
                </div>
                <!-- Empty State -->
                <div v-else-if="!props.responses.data?.length" class="p-8 text-center">
                    <MessageSquare class="w-12 h-12 mx-auto mb-3 text-gray-300" aria-hidden="true" />
                    <p class="text-gray-500 font-medium">Nenhuma resposta recebida ainda</p>
                    <p class="text-sm text-gray-400 mt-1 max-w-md mx-auto">
                        Compartilhe o formulário para começar a coletar respostas dos usuários.
                    </p>
                    <Button v-if="shareUrl" variant="outline" @click="copyShareLink" class="mt-4 gap-2">
                        <Share2 class="w-4 h-4" />
                        Copiar Link de Compartilhamento
                    </Button>
                </div>
                <!-- Lista de Respostas -->
                <div v-else class="divide-y divide-gray-200">
                    <div v-for="response in props.responses.data" :key="response.id"
                        class="p-4 hover:bg-gray-50 transition-colors">
                        <!-- Cabeçalho da resposta -->
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-full bg-gradient-to-br from-cyan-400 to-cyan-600 flex items-center justify-center text-white font-semibold flex-shrink-0">
                                    {{ response.respondent ? response.respondent.charAt(0).toUpperCase() : '?' }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900 truncate">
                                        {{ response.respondent || 'Anônimo' }}
                                    </p>
                                    <p class="text-sm text-gray-500 truncate">
                                        {{ response.email || 'Sem email' }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="text-sm text-gray-500">
                                    {{ formatDateTime(response.created_at) }}
                                </p>
                                <p class="text-xs text-gray-400 font-mono">
                                    IP: {{ response.ip_address || 'N/A' }}
                                </p>
                                <Button v-if="!response.status_paciente" variant="outline" size="sm" class="mt-2 gap-1 text-xs"
                                    @click="openPatientDialog(response)">
                                    <UserPlus class="w-3.5 h-3.5" />
                                    Criar Paciente
                                </Button>
                                <span v-else class="inline-flex items-center gap-1 text-xs text-green-600 font-medium mt-2">
                                    <CheckCircle2 class="w-3.5 h-3.5" />
                                    Paciente criado
                                </span>
                            </div>
                        </div>
                        <!-- Respostas por campo -->
                        <div class="space-y-3 mt-3 bg-gray-50 p-4 rounded-lg">
                            <FormField v-for="field in props.form.fields" :key="field.id" :field="field"
                                :model-value="response.answers?.[field.id]" disabled class="bg-white" />
                        </div>
                    </div>
                </div>
                <!-- Paginação -->
                <div v-if="props.responses.links?.length > 3" class="p-4 border-t border-gray-200 bg-gray-50">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <p class="text-sm text-gray-500">
                            Mostrando <span class="font-medium">{{ props.responses.from }}</span>
                            a <span class="font-medium">{{ props.responses.to }}</span>
                            de <span class="font-medium">{{ props.responses.total }}</span> resultados
                        </p>
                        <div class="flex gap-1 flex-wrap justify-center">
                            <Button v-for="(link, index) in props.responses.links" :key="index"
                                :variant="link.active ? 'default' : 'outline'" :disabled="!link.url || loading"
                                @click.prevent="handlePagination(link.url)" class="px-3 py-1 text-sm h-8 min-w-[2rem]"
                                :v-html="link.label" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ==========================================
             TAB: ANÁLISE POR CAMPO
             ========================================== -->
        <div v-show="activeTab === 'analytics'" id="analytics-panel" role="tabpanel" aria-labelledby="tab-analytics"
            class="space-y-6">
            <!-- Sem dados -->
            <div v-if="!formattedStats.hasResponses || Object.keys(props.fieldStats).length === 0"
                class="bg-white p-8 rounded-xl border border-gray-200 text-center">
                <BarChart3 class="w-12 h-12 mx-auto mb-3 text-gray-300" aria-hidden="true" />
                <p class="text-gray-500 font-medium">Não há dados suficientes para análise</p>
                <p class="text-sm text-gray-400 mt-1 max-w-md mx-auto">
                    {{ !formattedStats.hasResponses
                        ? 'Aguarde receber respostas para visualizar estatísticas detalhadas.'
                        : 'Os campos deste formulário não possuem dados analisáveis.'
                    }}
                </p>
            </div>
            <!-- Cards de estatísticas por campo -->
            <div v-for="(stat, fieldId) in props.fieldStats" :key="fieldId"
                class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">{{ stat.label }}</h3>
                    <span class="px-2 py-1 rounded text-xs bg-gray-100 text-gray-600 capitalize">
                        {{ stat.type }}
                    </span>
                </div>
                <div class="space-y-4">
                    <div v-for="[option, count] in Object.entries(stat.data)" :key="option"
                        class="flex items-center gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm text-gray-700 font-medium truncate">
                                    {{ option || 'Sem resposta' }}
                                </span>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <span class="text-sm font-bold text-gray-900">{{ count }}</span>
                                    <span class="text-xs text-gray-500">
                                        ({{ props.stats.total_responses > 0
                                            ? Math.round((count / props.stats.total_responses) * 100)
                                            : 0 }}%)
                                    </span>
                                </div>
                            </div>
                            <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-cyan-400 to-cyan-600 rounded-full transition-all duration-500"
                                    :style="{
                                        width: props.stats.total_responses > 0
                                            ? (count / props.stats.total_responses) * 100 + '%'
                                            : '0%'
                                    }"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Resumo -->
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <p class="text-sm text-gray-500">
                        Total de respostas para este campo:
                        <span class="font-medium text-gray-900">
                            {{Object.values(stat.data).reduce((a, b) => a + b, 0)}}
                        </span>
                    </p>
                </div>
            </div>
        </div>
        <CreatePatientFromResponseModal :open="patientDialogOpen" :response="selectedResponse"
            :fields="props.form.fields" @update:open="patientDialogOpen = $event" />
    </TenantAdminLayout>
</template>
