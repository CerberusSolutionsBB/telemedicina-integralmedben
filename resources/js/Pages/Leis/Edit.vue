<script setup>
import { computed, ref } from "vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import { Label } from "@/Components/ui/label";
import CentralAdminLayout from "@/Layouts/CentralAdminLayout.vue";
import { showToast } from '@/Utils/toast';
import RichTextEditor from '@/Components/RichTextEditor.vue'
import Breadcrumb from "@/Components/Breadcrumb.vue";
import {
    ArrowLeft,
    Save,
    FileText,
    Home,
    Scale,
    Info,
    AlertCircle,
    Maximize2,
    X,
    Eye
} from "lucide-vue-next";

const props = defineProps({
    lei: Object,
    tipos: Object, // { lgpd: 'LGPD...', consolidada: 'Normas...', ... }
});

const breadcrumbs = computed(() => [
    { label: 'Início', href: route('dashboard'), icon: Home },
    { label: 'Leis', href: route('leis.index') },
    { label: props.lei.title, href: route('leis.show', props.lei.id) },
    { label: 'Editar', href: null },
]);

// Tipos de leis (mesmo do Create.vue, ou pode usar props.tipos)
const tiposLeis = [
    { value: 'lgpd', label: 'LGPD (Lei Geral de Proteção de Dados)' },
    { value: 'consolidada', label: 'Normas Consolidadas' },
    { value: 'codigo_etica', label: 'Código de Ética' },
    { value: 'politica_privacidade', label: 'Política de Privacidade' },
    { value: 'termo_uso', label: 'Termo de Uso' },
    { value: 'outro', label: 'Outro' },
];

const inputClass = "w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-500";

// Form preenchido com dados existentes
const form = useForm({
    title: props.lei.title,
    type: props.lei.type,
    status: props.lei.status, // 'rascunho', 'ativo', 'inativo'
    text: props.lei.text,
});

const showFullPreview = ref(false);

const submit = () => {
    form.put(route('leis.update', props.lei.id), {
        preserveScroll: true,
        onSuccess: () => {
            showToast('Lei atualizada com sucesso!', 'success');
        },
        onError: (errors) => {
            const message = errors.title || errors.text || 'Erro ao atualizar lei';
            showToast(message, 'error');
        }
    });
};

const goBack = () => {
    router.visit(route('leis.show', props.lei.id));
};

const goToIndex = () => {
    router.visit(route('leis.index'));
};

const getTextLength = (html) => {
    if (!html) return 0;
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    return (tmp.textContent || tmp.innerText || '').length;
};
</script>

<template>

    <Head title="Editar Lei" />
    <CentralAdminLayout>
        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-2 mb-6">
            <div class="space-y-1">
                <Breadcrumb :items="breadcrumbs" />
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">
                    Editar Lei
                </h1>
                <p class="text-sm text-gray-500">
                    Modifique as informações da norma ou documento
                </p>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" @click="goBack" class="gap-2">
                    <ArrowLeft class="w-4 h-4" />
                    Voltar
                </Button>
                <Button variant="outline" @click="goToIndex" class="gap-2">
                    <Eye class="w-4 h-4" />
                    Ver Listagem
                </Button>
                <Button variant="primary" @click="submit" :disabled="form.processing || !form.title" class="gap-2">
                    <Save class="w-4 h-4" />
                    <span v-if="form.processing">Salvando...</span>
                    <span v-else>Salvar Alterações</span>
                </Button>
            </div>
        </div>

        <div class="space-y-6 mx-auto">
            <!-- Alerta de Erros -->
            <div v-if="Object.keys(form.errors).length > 0" class="bg-red-50 border border-red-200 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <AlertCircle class="w-5 h-5 text-red-600 mt-0.5" />
                    <div>
                        <h3 class="font-semibold text-red-900 mb-1">Corrija os erros abaixo:</h3>
                        <ul class="text-sm text-red-800 space-y-1">
                            <li v-for="(error, key) in form.errors" :key="key">
                                {{ error }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Layout Principal: 2 colunas -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                <!-- COLUNA ESQUERDA: Formulário -->
                <div class="space-y-6">
                    <!-- Dicas -->
                    <div class="bg-blue-50 rounded-xl border border-blue-200 p-4">
                        <div class="flex items-start gap-3">
                            <Info class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" />
                            <div>
                                <h3 class="font-semibold text-blue-900 mb-1">Dicas de Edição</h3>
                                <ul class="text-sm text-blue-800 space-y-2 list-disc list-inside">
                                    <li>Revise o título antes de salvar</li>
                                    <li>Altere o status para <strong>ativo</strong> quando pronto</li>
                                    <li>O conteúdo HTML é preservado</li>
                                    <li>Use o preview para verificar alterações</li>
                                    <li>Alterações são registradas automaticamente</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Formulário -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                        <div
                            class="flex items-center gap-2 mb-6 text-gray-900 font-semibold border-b border-gray-100 pb-4">
                            <Scale class="w-5 h-5 text-cyan-600" />
                            <h2>Informações da Lei</h2>
                        </div>

                        <div class="space-y-6">
                            <!-- Título -->
                            <div>
                                <Label for="title" class="flex items-center gap-1 text-gray-700 pb-2 font-medium">
                                    Título
                                    <span class="text-red-500">*</span>
                                </Label>
                                <input id="title" v-model="form.title" type="text"
                                    placeholder="Ex: Lei Geral de Proteção de Dados Pessoais"
                                    :class="[inputClass, form.errors.title ? 'border-red-500 focus:ring-red-500' : '']"
                                    :disabled="form.processing" />
                                <p v-if="form.errors.title" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.title }}
                                </p>
                            </div>

                            <!-- Tipo e Status -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <Label for="type" class="text-gray-700 pb-2 font-medium">Tipo</Label>
                                    <select id="type" v-model="form.type"
                                        :class="[inputClass, form.errors.type ? 'border-red-500 focus:ring-red-500' : '']"
                                        :disabled="form.processing">
                                        <option v-for="tipo in tiposLeis" :key="tipo.value" :value="tipo.value">
                                            {{ tipo.label }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <Label for="status" class="text-gray-700 pb-2 font-medium">Status</Label>
                                    <select id="status" v-model="form.status"
                                        :class="[inputClass, form.errors.status ? 'border-red-500 focus:ring-red-500' : '']"
                                        :disabled="form.processing">
                                        <option value="rascunho">Rascunho</option>
                                        <option value="ativo">Ativo</option>
                                        <option value="inativo">Inativo</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Conteúdo -->
                            <div>
                                <Label class="text-gray-700 pb-2 font-medium">
                                    Conteúdo
                                    <span class="text-red-500">*</span>
                                </Label>
                                <RichTextEditor v-model="form.text" placeholder="Digite o conteúdo..." />
                                <p v-if="form.errors.text" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.text }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    Use o editor para formatar o texto. O conteúdo será salvo com formatação HTML.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLUNA DIREITA: Preview -->
                <div class="lg:sticky lg:top-6">
                    <div class="bg-gray-50 rounded-xl border border-gray-200 p-6">
                        <!-- Header do Preview -->
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold text-gray-700 flex items-center gap-2">
                                <FileText class="w-4 h-4" />
                                Preview das Alterações
                            </h3>
                            <Button v-if="form.text" variant="outline" size="sm" @click="showFullPreview = true"
                                class="gap-1 text-xs">
                                <Maximize2 class="w-3 h-3" />
                                Ver completo
                            </Button>
                        </div>

                        <!-- Card do Preview -->
                        <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
                            <!-- Tag do tipo -->
                            <div class="px-4 py-2 border-b border-gray-100">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="{
                                        'bg-blue-100 text-blue-800': form.type === 'lgpd',
                                        'bg-purple-100 text-purple-800': form.type === 'consolidada',
                                        'bg-green-100 text-green-800': form.type === 'codigo_etica',
                                        'bg-yellow-100 text-yellow-800': form.type === 'politica_privacidade',
                                        'bg-gray-100 text-gray-800': form.type === 'termo_uso' || form.type === 'outro'
                                    }">
                                    {{tiposLeis.find(t => t.value === form.type)?.label || form.type}}
                                </span>
                            </div>

                            <!-- Conteúdo -->
                            <div class="p-4">
                                <h4 class="font-semibold text-gray-900 text-lg leading-tight mb-2 break-words">
                                    {{ form.title || 'Título da Lei' }}
                                </h4>
                                <div class="flex items-center gap-2 text-xs mb-3">
                                    <span class="text-gray-500">Status:</span>
                                    <span :class="{
                                        'text-yellow-600 font-medium': form.status === 'rascunho',
                                        'text-green-600 font-medium': form.status === 'ativo',
                                        'text-red-600 font-medium': form.status === 'inativo'
                                    }">
                                        {{ form.status }}
                                    </span>
                                </div>

                                <!-- Área de conteúdo -->
                                <div
                                    class="mt-3 p-3 bg-gray-50 rounded-lg border max-h-[300px] overflow-y-auto overflow-x-hidden">
                                    <div v-if="form.text" class="text-sm text-gray-700 break-words whitespace-pre-wrap"
                                        v-html="form.text">
                                    </div>
                                    <p v-else class="text-sm text-gray-400 italic">
                                        Conteúdo aparecerá aqui...
                                    </p>
                                </div>

                                <!-- Info de caracteres -->
                                <div class="mt-3 flex justify-between items-center text-xs text-gray-500">
                                    <span>{{ getTextLength(form.text) }} caracteres</span>
                                    <span v-if="form.text && form.text.length > 1000" class="text-cyan-600">
                                        Preview limitado
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Info adicional -->
                    <div class="mt-4 bg-white rounded-xl border border-gray-200 p-4">
                        <h4 class="font-medium text-gray-700 mb-2 text-sm">Resumo das Alterações</h4>
                        <div class="space-y-2 text-sm text-gray-500">
                            <div class="flex justify-between">
                                <span>Caracteres:</span>
                                <span class="font-mono">{{ getTextLength(form.text) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Tipo:</span>
                                <span class="text-right text-xs">{{tiposLeis.find(t => t.value === form.type)?.label
                                }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Status:</span>
                                <span :class="{
                                    'text-yellow-600': form.status === 'rascunho',
                                    'text-green-600': form.status === 'ativo',
                                    'text-red-600': form.status === 'inativo'
                                }">{{ form.status }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: Preview Completo -->
        <div v-if="showFullPreview" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" @click="showFullPreview = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col">
                <div class="flex items-center justify-between p-4 border-b">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="{
                            'bg-blue-100 text-blue-800': form.type === 'lgpd',
                            'bg-purple-100 text-purple-800': form.type === 'consolidada',
                            'bg-green-100 text-green-800': form.type === 'codigo_etica',
                            'bg-yellow-100 text-yellow-800': form.type === 'politica_privacidade',
                            'bg-gray-100 text-gray-800': form.type === 'termo_uso' || form.type === 'outro'
                        }">
                            {{tiposLeis.find(t => t.value === form.type)?.label}}
                        </span>
                        <h3 class="font-semibold text-gray-900 break-words max-w-md">
                            {{ form.title || 'Título da Lei' }}
                        </h3>
                    </div>
                    <button @click="showFullPreview = false" class="p-2 hover:bg-gray-100 rounded-lg">
                        <X class="w-5 h-5 text-gray-500" />
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto overflow-x-hidden p-6">
                    <div class="prose prose-lg max-w-none break-words" v-html="form.text || '<p>Sem conteúdo</p>'">
                    </div>
                </div>
                <div class="p-4 border-t bg-gray-50 flex justify-between items-center">
                    <span class="text-sm text-gray-500">
                        Status: <span class="font-medium" :class="{
                            'text-yellow-600': form.status === 'rascunho',
                            'text-green-600': form.status === 'ativo',
                            'text-red-600': form.status === 'inativo'
                        }">{{ form.status }}</span>
                    </span>
                    <Button variant="outline" @click="showFullPreview = false">
                        Fechar
                    </Button>
                </div>
            </div>
        </div>
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
}

.prose pre {
    white-space: pre-wrap;
    word-wrap: break-word;
    overflow-x: hidden;
}

.break-words {
    word-break: break-word;
    overflow-wrap: break-word;
}
</style>
