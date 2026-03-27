<script setup>
import { ref, computed } from "vue";
import { Head, router, Link } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import CentralAdminLayout from "@/Layouts/CentralAdminLayout.vue";
import { showToast } from '@/Utils/toast';
import FormField from "@/Components/FormFields/FormField.vue"; // NOVO

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
    Home
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

const statusColors = {
    rascunho: 'bg-gray-100 text-gray-800',
    ativo: 'bg-green-100 text-green-800',
    pausado: 'bg-yellow-100 text-yellow-800',
    encerrado: 'bg-red-100 text-red-800'
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
    { label: form.title, href: null }
]);

const copyShareLink = () => {
    if (props.shareUrl) {
        navigator.clipboard.writeText(props.shareUrl);
        showToast('Link copiado!', 'success');
    }
};

const deleteForm = () => {
    if (confirm('Excluir este formulário?')) {
        router.delete(route('forms.destroy', props.form.id), {
            onSuccess: () => showToast('Excluído!', 'success'),
        });
    }
};

const formatAnswer = (answer, fieldType) => {
    if (answer === null || answer === undefined || answer === '') {
        return '-';
    }
    if (Array.isArray(answer)) {
        return answer.join(', ');
    }
    if (fieldType === 'checkbox' && typeof answer === 'object') {
        return Object.entries(answer)
            .filter(([_, v]) => v)
            .map(([k, _]) => k)
            .join(', ');
    }
    return answer;
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('pt-BR');
};
</script>

<template>

    <Head :title="form.title" />

    <CentralAdminLayout>
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm text-gray-500 mb-4">
            <Link :href="route('dashboard')" class="hover:text-cyan-600 flex items-center gap-1">
                <Home class="w-4 h-4" /> Início
            </Link>
            <span>/</span>
            <Link :href="route('forms.index')" class="hover:text-cyan-600">Formulários</Link>
            <span>/</span>
            <span class="text-gray-900 font-medium truncate max-w-xs">{{ form.title }}</span>
        </nav>

        <!-- Header -->
        <div class="mb-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <h1 class="text-2xl font-bold text-gray-900">{{ form.title }}</h1>
                        <span :class="['px-2.5 py-0.5 rounded-full text-xs font-medium', statusColors[form.status]]">
                            {{ statusLabels[form.status] }}
                        </span>
                    </div>
                    <p class="text-gray-600 max-w-2xl">{{ form.description || 'Sem descrição' }}</p>

                    <div class="flex flex-wrap items-center gap-4 mt-3 text-sm text-gray-500">
                        <span class="flex items-center gap-1">
                            <Users class="w-4 h-4" /> {{ form.created_by }}
                        </span>
                        <span v-if="form.published_at" class="flex items-center gap-1">
                            <Calendar class="w-4 h-4" /> Publicado em {{ form.published_at }}
                        </span>
                        <span class="flex items-center gap-1">
                            <Globe v-if="form.is_public" class="w-4 h-4 text-green-600" />
                            <Lock v-else class="w-4 h-4" />
                            {{ form.is_public ? 'Público' : 'Privado' }}
                        </span>
                        <span v-if="form.code" class="text-xs text-gray-400 font-mono">
                            Código: {{ form.code.substring(0, 8) }}...
                        </span>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Button v-if="shareUrl" variant="outline" @click="copyShareLink" class="gap-2">
                        <Share2 class="w-4 h-4" /> Copiar Link
                    </Button>

                    <Button v-if="can.edit" variant="outline" class="gap-2">
                        <Edit class="w-4 h-4" /> Editar
                    </Button>

                    <Button v-if="can.delete" variant="destructive" @click="deleteForm" class="gap-2">
                        <Trash2 class="w-4 h-4" /> Excluir
                    </Button>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8">
                <button @click="activeTab = 'overview'"
                    :class="['py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors', activeTab === 'overview' ? 'border-cyan-500 text-cyan-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
                    <BarChart3 class="w-4 h-4" /> Visão Geral
                </button>
                <button @click="activeTab = 'responses'"
                    :class="['py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors', activeTab === 'responses' ? 'border-cyan-500 text-cyan-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
                    <MessageSquare class="w-4 h-4" /> Respostas
                    <span class="ml-1 px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-800">
                        {{ form.responses_count }}
                    </span>
                </button>
                <button @click="activeTab = 'analytics'"
                    :class="['py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors', activeTab === 'analytics' ? 'border-cyan-500 text-cyan-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
                    <Eye class="w-4 h-4" /> Análise por Campo
                </button>
            </nav>
        </div>

        <!-- TAB: Visão Geral -->
        <div v-if="activeTab === 'overview'" class="space-y-6">
            <!-- Cards de Estatísticas -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total de Respostas</p>
                            <p class="text-3xl font-bold text-gray-900 mt-1">{{ stats.total_responses || 0 }}</p>
                        </div>
                        <div class="p-3 bg-cyan-50 rounded-lg">
                            <MessageSquare class="w-6 h-6 text-cyan-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Taxa de Resposta</p>
                            <p class="text-3xl font-bold text-gray-900 mt-1">
                                {{ form.response_limit ? Math.round((stats.total_responses / form.response_limit) * 100)
                                    + '%' : 'N/A' }}
                            </p>
                        </div>
                        <div class="p-3 bg-green-50 rounded-lg">
                            <CheckCircle2 class="w-6 h-6 text-green-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Última Resposta</p>
                            <p class="text-lg font-bold text-gray-900 mt-1">
                                {{ stats.last_response_at || 'Nenhuma' }}
                            </p>
                        </div>
                        <div class="p-3 bg-blue-50 rounded-lg">
                            <Clock class="w-6 h-6 text-blue-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Status</p>
                            <p class="text-lg font-bold mt-1" :class="statusColors[form.status]">
                                {{ statusLabels[form.status] }}
                            </p>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <Globe class="w-6 h-6 text-gray-600" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráfico de Respostas por Dia -->
            <div v-if="stats.daily_responses && stats.daily_responses.length > 0"
                class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Respostas nos Últimos Dias</h3>
                <div class="space-y-3">
                    <div v-for="day in stats.daily_responses.slice(0, 10)" :key="day.date"
                        class="flex items-center gap-4">
                        <div class="w-24 text-sm text-gray-600">
                            {{ formatDate(day.date) }}
                        </div>
                        <div class="flex-1 bg-gray-100 rounded-full h-6 overflow-hidden">
                            <div class="h-full bg-cyan-500 rounded-full transition-all"
                                :style="{ width: Math.min((day.count / Math.max(...stats.daily_responses.map(d => d.count))) * 100, 100) + '%' }">
                            </div>
                        </div>
                        <div class="w-12 text-sm font-medium text-gray-900 text-right">
                            {{ day.count }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estrutura do Formulário com FormField -->
            <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Estrutura do Formulário ({{ form.fields.length }}
                    campos)</h3>
                <div class="space-y-4">
                    <div v-for="(field, index) in form.fields" :key="field.id"
                        class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-gray-400 font-mono">#{{ index + 1 }}</span>
                                    <p class="font-medium text-gray-900">
                                        {{ field.label || 'Campo sem título' }}
                                        <span v-if="field.required" class="text-red-500">*</span>
                                    </p>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">
                                    Tipo: <span class="font-medium">{{ field.type }}</span>
                                    <span v-if="field.options?.length">| {{ field.options.length }} opções</span>
                                </p>
                                <p v-if="field.help_text" class="text-sm text-gray-600 mt-1">
                                    {{ field.help_text }}
                                </p>
                            </div>
                            <span class="px-2 py-1 rounded border text-xs border-gray-300 text-gray-600 bg-white">
                                {{ field.type }}
                            </span>
                        </div>
                        <!-- Preview do campo com FormField -->
                        <FormField :field="field" disabled class="bg-white" />
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB: Respostas -->
        <div v-if="activeTab === 'responses'" class="space-y-6">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Respostas Individuais</h3>
                    <span class="text-sm text-gray-500">{{ responses.total || 0 }} total</span>
                </div>

                <div v-if="!responses.data || responses.data.length === 0" class="p-8 text-center text-gray-500">
                    <MessageSquare class="w-12 h-12 mx-auto mb-3 text-gray-300" />
                    <p>Nenhuma resposta recebida ainda</p>
                </div>

                <div v-else class="divide-y divide-gray-200">
                    <div v-for="response in responses.data" :key="response.id"
                        class="p-4 hover:bg-gray-50 transition-colors">
                        <!-- Cabeçalho da resposta -->
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-full bg-gradient-to-br from-cyan-400 to-cyan-600 flex items-center justify-center text-white font-semibold">
                                    {{ response.respondent ? response.respondent.charAt(0).toUpperCase() : '?' }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ response.respondent || 'Anônimo' }}</p>
                                    <p class="text-sm text-gray-500">{{ response.email || 'Sem email' }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500">{{ response.created_at }}</p>
                                <p class="text-xs text-gray-400">IP: {{ response.ip_address || 'N/A' }}</p>
                            </div>
                        </div>

                        <!-- Respostas por campo com FormField -->
                        <div class="space-y-3 mt-3 bg-gray-50 p-4 rounded-lg">
                            <FormField v-for="field in form.fields" :key="field.id" :field="field"
                                :model-value="response.answers?.[field.id]" disabled class="bg-white" />
                        </div>
                    </div>
                </div>

                <!-- Paginação -->
                <div v-if="responses.links && responses.links.length > 3" class="p-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-500">
                            Mostrando {{ responses.from }} a {{ responses.to }} de {{ responses.total }}
                        </p>
                        <div class="flex gap-1">
                            <Button v-for="(link, index) in responses.links" :key="index"
                                :variant="link.active ? 'primary' : 'outline'" :disabled="!link.url"
                                @click="link.url && router.get(link.url, {}, { preserveState: true, preserveScroll: true })"
                                class="px-3 py-1 text-sm h-8 min-w-[2rem]" v-html="link.label" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB: Análise por Campo -->
        <div v-if="activeTab === 'analytics'" class="space-y-6">
            <div v-if="Object.keys(fieldStats).length === 0"
                class="bg-white p-8 rounded-xl border border-gray-200 text-center">
                <BarChart3 class="w-12 h-12 mx-auto mb-3 text-gray-300" />
                <p class="text-gray-500">Não há dados suficientes para análise</p>
                <p class="text-sm text-gray-400 mt-1">Aguarde mais respostas para visualizar estatísticas</p>
            </div>

            <div v-for="(stat, fieldId) in fieldStats" :key="fieldId"
                class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">{{ stat.label }}</h3>
                    <span class="px-2 py-1 rounded text-xs bg-gray-100 text-gray-600">
                        {{ stat.type }}
                    </span>
                </div>

                <div class="space-y-4">
                    <div v-for="[option, count] in Object.entries(stat.data)" :key="option"
                        class="flex items-center gap-4">
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm text-gray-700 font-medium">{{ option }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-bold text-gray-900">{{ count }}</span>
                                    <span class="text-xs text-gray-500">
                                        ({{ stats.total_responses > 0 ? Math.round((count / stats.total_responses) *
                                            100) : 0 }}%)
                                    </span>
                                </div>
                            </div>
                            <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-cyan-400 to-cyan-600 rounded-full transition-all"
                                    :style="{ width: stats.total_responses > 0 ? (count / stats.total_responses) * 100 + '%' : '0%' }">
                                </div>
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
    </CentralAdminLayout>
</template>
