<script setup>
import { computed, ref } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import { Label } from "@/Components/ui/label";
import CentralAdminLayout from "@/Layouts/CentralAdminLayout.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import {
    ArrowLeft,
    Edit,
    FileText,
    Home,
    Scale,
    User,
    Calendar,
    Clock,
    CheckCircle2,
    XCircle,
    AlertCircle,
    Trash2,
    ArchiveRestore,
    Copy,
    ExternalLink,
    Eye,
    History,
    Hash
} from "lucide-vue-next";

const props = defineProps({
    lei: Object,
    flash: Object,
});

const breadcrumbs = computed(() => [
    { label: 'Início', href: route('dashboard'), icon: Home },
    { label: 'Leis', href: route('leis.index') },
    { label: props.lei.title, href: null },
]);

const showDeleteModal = ref(false);
const showRestoreModal = ref(false);
const isProcessing = ref(false);

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getStatusConfig = (status) => {
    const configs = {
        'ativo': {
            color: 'bg-green-100 text-green-800 border-green-200',
            icon: CheckCircle2,
            iconColor: 'text-green-600',
            label: 'Publicado'
        },
        'inativo': {
            color: 'bg-red-100 text-red-800 border-red-200',
            icon: XCircle,
            iconColor: 'text-red-600',
            label: 'Desativado'
        },
        'rascunho': {
            color: 'bg-yellow-100 text-yellow-800 border-yellow-200',
            icon: Clock,
            iconColor: 'text-yellow-600',
            label: 'Em edição'
        },
    };
    return configs[status] || configs['rascunho'];
};

const getTypeConfig = (type) => {
    const configs = {
        'lgpd': { color: 'bg-blue-100 text-blue-800 border-blue-200', icon: Scale },
        'consolidada': { color: 'bg-purple-100 text-purple-800 border-purple-200', icon: FileText },
        'codigo_etica': { color: 'bg-emerald-100 text-emerald-800 border-emerald-200', icon: CheckCircle2 },
        'politica_privacidade': { color: 'bg-amber-100 text-amber-800 border-amber-200', icon: Eye },
        'termo_uso': { color: 'bg-orange-100 text-orange-800 border-orange-200', icon: FileText },
        'outro': { color: 'bg-gray-100 text-gray-800 border-gray-200', icon: FileText },
    };
    return configs[type] || configs['outro'];
};

const confirmDelete = () => {
    isProcessing.value = true;
    router.delete(route('leis.destroy', props.lei.id), {
        onFinish: () => {
            isProcessing.value = false;
            showDeleteModal.value = false;
        },
        preserveScroll: true,
    });
};

const confirmRestore = () => {
    isProcessing.value = true;
    router.post(route('leis.restore', props.lei.id), {}, {
        onFinish: () => {
            isProcessing.value = false;
            showRestoreModal.value = false;
        },
        preserveScroll: true,
    });
};

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
    // Pode adicionar toast aqui
};
</script>

<template>

    <Head :title="lei.title" />
    <CentralAdminLayout>
        <!-- Flash Messages Animados -->
        <Transition enter-active-class="transition ease-out duration-300" enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100" leave-to-class="opacity-0 -translate-y-2">
            <div v-if="flash?.success" class="mb-6 bg-green-50 border border-green-200 rounded-xl p-4 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="p-1 bg-green-100 rounded-full">
                        <CheckCircle2 class="w-5 h-5 text-green-600" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-green-900">Sucesso!</h3>
                        <p class="text-sm text-green-800">{{ flash.success }}</p>
                    </div>
                </div>
            </div>
        </Transition>

        <Transition enter-active-class="transition ease-out duration-300" enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100" leave-to-class="opacity-0 -translate-y-2">
            <div v-if="flash?.error" class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="p-1 bg-red-100 rounded-full">
                        <AlertCircle class="w-5 h-5 text-red-600" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-red-900">Erro</h3>
                        <p class="text-sm text-red-800">{{ flash.error }}</p>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Header Moderno -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div class="space-y-1">
                <Breadcrumb :items="breadcrumbs" />
                <div class="flex items-center gap-3">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">
                        Detalhes da Lei
                    </h1>
                    <!-- Badge de ID -->
                    <button @click="copyToClipboard(lei.id)"
                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors"
                        title="Clique para copiar ID">
                        <Hash class="w-3 h-3" />
                        #{{ lei.id }}
                    </button>
                </div>
                <p class="text-sm text-gray-500">
                    Visualize informações completas e gerencie o documento
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button variant="outline" :href="route('leis.index')" class="gap-2">
                    <ArrowLeft class="w-4 h-4" />
                    Voltar
                </Button>
                <Button v-if="!lei.deleted_at" variant="outline" :href="route('leis.edit', lei.id)" class="gap-2">
                    <Edit class="w-4 h-4" />
                    Editar
                </Button>
            </div>
        </div>

        <!-- Banner de Excluído -->
        <div v-if="lei.deleted_at"
            class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-red-100 rounded-full">
                    <Trash2 class="w-5 h-5 text-red-600" />
                </div>
                <div>
                    <h3 class="font-semibold text-red-900">Documento Excluído</h3>
                    <p class="text-sm text-red-700">
                        Esta lei foi movida para a lixeira em {{ formatDate(lei.deleted_at) }}
                    </p>
                </div>
            </div>
            <Button variant="primary" class="gap-2 bg-green-600 hover:bg-green-700" @click="showRestoreModal = true">
                <ArchiveRestore class="w-4 h-4" />
                Restaurar
            </Button>
        </div>

        <div class="space-y-6 mx-auto">
            <!-- Layout Principal -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

                <!-- COLUNA ESQUERDA: Sidebar de Informações (4/12) -->
                <div class="space-y-6 lg:col-span-4">

                    <!-- Card Principal de Status -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                        <!-- Header com cor dinâmica -->
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                            <div class="flex items-center gap-2 text-gray-900 font-semibold">
                                <Scale class="w-5 h-5 text-cyan-600" />
                                <h2>Informações</h2>
                            </div>
                        </div>

                        <div class="p-6 space-y-5">
                            <!-- Status Badge Grande -->
                            <div class="flex items-center justify-between">
                                <Label class="text-gray-500 text-sm">Status Atual</Label>
                                <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg border"
                                    :class="getStatusConfig(lei.status).color">
                                    <component :is="getStatusConfig(lei.status).icon"
                                        :class="['w-4 h-4', getStatusConfig(lei.status).iconColor]" />
                                    <span class="text-sm font-medium capitalize">{{ lei.status }}</span>
                                </div>
                            </div>

                            <!-- Tipo -->
                            <div class="flex items-center justify-between">
                                <Label class="text-gray-500 text-sm">Tipo</Label>
                                <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg border"
                                    :class="getTypeConfig(lei.type).color">
                                    <component :is="getTypeConfig(lei.type).icon" class="w-4 h-4" />
                                    <span class="text-sm font-medium">{{ lei.type_label }}</span>
                                </div>
                            </div>

                            <!-- Divisor -->
                            <hr class="border-gray-100" />

                            <!-- Autor -->
                            <div>
                                <Label class="text-gray-500 text-sm mb-2 block">Criado por</Label>
                                <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                                    <div
                                        class="w-10 h-10 rounded-full bg-cyan-100 flex items-center justify-center flex-shrink-0">
                                        <User class="w-5 h-5 text-cyan-600" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ lei.user.name }}</p>
                                        <p class="text-xs text-gray-500 truncate">{{ lei.user.email }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Timeline -->
                            <div class="space-y-3">
                                <Label class="text-gray-500 text-sm">Histórico</Label>

                                <div class="flex items-start gap-3 text-sm">
                                    <div class="mt-0.5">
                                        <Calendar class="w-4 h-4 text-gray-400" />
                                    </div>
                                    <div>
                                        <p class="text-gray-500">Criado em</p>
                                        <p class="font-medium text-gray-900">{{ formatDate(lei.created_at) }}</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3 text-sm">
                                    <div class="mt-0.5">
                                        <History class="w-4 h-4 text-gray-400" />
                                    </div>
                                    <div>
                                        <p class="text-gray-500">Última atualização</p>
                                        <p class="font-medium text-gray-900">{{ formatDate(lei.updated_at) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card de Ações -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                        <h3 class="font-semibold text-gray-900 mb-4 text-sm flex items-center gap-2">
                            <ExternalLink class="w-4 h-4" />
                            Ações Rápidas
                        </h3>

                        <div class="space-y-2">
                            <Button v-if="!lei.deleted_at" variant="outline"
                                class="w-full justify-start gap-2 text-red-600 hover:bg-red-50 hover:text-red-700 border-red-200"
                                @click="showDeleteModal = true">
                                <Trash2 class="w-4 h-4" />
                                Mover para Lixeira
                            </Button>

                            <Button v-else variant="outline"
                                class="w-full justify-start gap-2 text-green-600 hover:bg-green-50 hover:text-green-700 border-green-200"
                                @click="showRestoreModal = true">
                                <ArchiveRestore class="w-4 h-4" />
                                Restaurar Documento
                            </Button>

                            <Button variant="outline" class="w-full justify-start gap-2 text-gray-600 hover:bg-gray-50"
                                @click="copyToClipboard(window.location.href)">
                                <Copy class="w-4 h-4" />
                                Copiar Link
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- COLUNA DIREITA: Conteúdo (8/12) -->
                <div class="lg:col-span-8">
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                        <!-- Header do Conteúdo -->
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                            <div class="flex items-center gap-2 text-gray-900 font-semibold">
                                <FileText class="w-5 h-5 text-cyan-600" />
                                <h2>Conteúdo Completo</h2>
                            </div>
                            <span class="text-xs text-gray-500">
                                {{ lei.text ? lei.text.length : 0 }} caracteres
                            </span>
                        </div>

                        <div class="p-6 sm:p-8">
                            <!-- Título -->
                            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-6 break-words leading-tight">
                                {{ lei.title }}
                            </h1>

                            <!-- Conteúdo HTML Estilizado -->
                            <article class="prose prose-lg max-w-none break-words text-gray-700" v-html="lei.text" />

                            <!-- Empty State -->
                            <div v-if="!lei.text" class="text-center py-16">
                                <div
                                    class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <FileText class="w-8 h-8 text-gray-400" />
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 mb-1">Sem conteúdo</h3>
                                <p class="text-gray-500">Esta lei não possui texto cadastrado.</p>
                                <Button v-if="!lei.deleted_at" variant="outline" class="mt-4 gap-2"
                                    :href="route('leis.edit', lei.id)">
                                    <Edit class="w-4 h-4" />
                                    Adicionar Conteúdo
                                </Button>
                            </div>
                        </div>

                        <!-- Footer do Card -->
                        <div v-if="lei.text"
                            class="px-6 py-3 bg-gray-50 border-t border-gray-100 text-xs text-gray-500 flex justify-between">
                            <span>ID: {{ lei.id }}</span>
                            <span>Última modificação: {{ formatDate(lei.updated_at) }}</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- MODAL: Confirmação de Exclusão -->
        <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showDeleteModal = false"></div>
                <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center gap-4 mb-4">
                            <div
                                class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <Trash2 class="w-6 h-6 text-red-600" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Confirmar Exclusão</h3>
                                <p class="text-sm text-gray-500">Tem certeza que deseja mover "{{ lei.title }}" para a
                                    lixeira?</p>
                            </div>
                        </div>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-6">
                            <p class="text-sm text-yellow-800">
                                <strong>Atenção:</strong> O documento poderá ser restaurado posteriormente.
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <Button variant="outline" class="flex-1" @click="showDeleteModal = false"
                                :disabled="isProcessing">
                                Cancelar
                            </Button>
                            <Button variant="primary" class="flex-1 bg-red-600 hover:bg-red-700 text-white gap-2"
                                @click="confirmDelete" :disabled="isProcessing">
                                <Trash2 v-if="!isProcessing" class="w-4 h-4" />
                                <span v-if="isProcessing">Excluindo...</span>
                                <span v-else>Sim, Excluir</span>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- MODAL: Confirmação de Restauração -->
        <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showRestoreModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showRestoreModal = false"></div>
                <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center gap-4 mb-4">
                            <div
                                class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <ArchiveRestore class="w-6 h-6 text-green-600" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Restaurar Documento</h3>
                                <p class="text-sm text-gray-500">Deseja restaurar "{{ lei.title }}"?</p>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <Button variant="outline" class="flex-1" @click="showRestoreModal = false"
                                :disabled="isProcessing">
                                Cancelar
                            </Button>
                            <Button variant="primary" class="flex-1 bg-green-600 hover:bg-green-700 text-white gap-2"
                                @click="confirmRestore" :disabled="isProcessing">
                                <ArchiveRestore v-if="!isProcessing" class="w-4 h-4" />
                                <span v-if="isProcessing">Restaurando...</span>
                                <span v-else>Sim, Restaurar</span>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </CentralAdminLayout>
</template>

<style>
.prose {
    max-width: 100%;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.prose img {
    max-width: 100%;
    height: auto;
    border-radius: 0.5rem;
}

.prose pre {
    white-space: pre-wrap;
    word-wrap: break-word;
    overflow-x: auto;
    background-color: #f3f4f6;
    padding: 1rem;
    border-radius: 0.5rem;
}

.prose blockquote {
    border-left: 4px solid #06b6d4;
    padding-left: 1rem;
    color: #4b5563;
    font-style: italic;
}

.prose table {
    width: 100%;
    border-collapse: collapse;
    margin: 1rem 0;
}

.prose th,
.prose td {
    border: 1px solid #e5e7eb;
    padding: 0.75rem;
    text-align: left;
}

.prose th {
    background-color: #f9fafb;
    font-weight: 600;
}

.break-words {
    word-break: break-word;
    overflow-wrap: break-word;
}

/* Animações suaves */
.transition-colors {
    transition-property: background-color, border-color, color, fill, stroke;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}
</style>
