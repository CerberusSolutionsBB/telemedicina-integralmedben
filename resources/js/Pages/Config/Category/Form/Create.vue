<script setup>
import { computed, ref } from "vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import { Label } from "@/Components/ui/label";
import CentralAdminLayout from "@/Layouts/CentralAdminLayout.vue";
import { showToast } from '@/Utils/toast';
import Breadcrumb from "@/Components/Breadcrumb.vue";
import {
    ArrowLeft,
    Save,
    Tag,
    Home,
    FileText,
    Info
} from "lucide-vue-next";

const breadcrumbs = computed(() => [
    { label: 'Início', href: route('dashboard'), icon: Home },
    { label: 'Configurações', href: null },
    { label: 'Categorias', href: route('configuracoes.categories.forms.index') },
    { label: isEdit.value ? 'Editar Categoria' : 'Nova Categoria', href: null },
]);

const props = defineProps({
    category: {
        type: Object,
        default: null
    }
});

const isEdit = computed(() => !!props.category);

const inputClass = "w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-500";

const form = useForm({
    name: props.category?.name || '',
    description: props.category?.description || ''
});

const submit = () => {
    const routeName = isEdit.value
        ? 'configuracoes.categories.forms.update'
        : 'configuracoes.categories.forms.store';

    const routeParams = isEdit.value
        ? { category: props.category.id }
        : {};

    form.post(route(routeName, routeParams), {
        preserveScroll: true,
        onSuccess: () => {
            showToast(
                isEdit.value ? 'Categoria atualizada com sucesso!' : 'Categoria criada com sucesso!',
                'success'
            );
            if (!isEdit.value) form.reset();
        },
        onError: (errors) => {
            const message = errors.name || errors.description || 'Erro ao salvar categoria';
            showToast(message, 'error');
        }
    });
};

const goBack = () => {
    router.visit(route('configuracoes.categories.forms.index'));
};
</script>

<template>

    <Head :title="isEdit ? 'Editar Categoria' : 'Nova Categoria'" />

    <CentralAdminLayout>
        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-2 mb-6">
            <div class="space-y-1">
                <Breadcrumb :items="breadcrumbs" />
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">
                    {{ isEdit ? 'Editar Categoria' : 'Nova Categoria' }}
                </h1>
                <p class="text-sm text-gray-500">
                    {{ isEdit
                        ? 'Atualize as informações da categoria selecionada'
                        : 'Preencha as informações para criar uma nova categoria de formulários'
                    }}
                </p>
            </div>

            <div class="flex gap-2">
                <Button variant="outline" @click="goBack" class="gap-2">
                    <ArrowLeft class="w-4 h-4" />
                    Voltar
                </Button>

                <Button variant="primary" @click="submit" :disabled="form.processing || !form.name" class="gap-2">
                    <Save class="w-4 h-4" />
                    <span v-if="form.processing">Salvando...</span>
                    <span v-else>{{ isEdit ? 'Atualizar' : 'Salvar' }}</span>
                </Button>
            </div>
        </div>

        <div class="space-y-6  mx-auto">

            <!-- Alerta/Dicas -->
            <div class="bg-blue-50 rounded-xl border border-blue-200 p-4">
                <div class="flex items-start gap-3">
                    <Info class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" />
                    <div>
                        <h3 class="font-semibold text-blue-900 mb-1">Dicas para criar categorias</h3>
                        <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
                            <li>Use nomes claros e objetivos</li>
                            <li>Evite categorias muito genéricas</li>
                            <li>A descrição ajuda outros administradores a entenderem a finalidade</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                        <div
                            class="flex items-center gap-2 mb-6 text-gray-900 font-semibold border-b border-gray-100 pb-4">
                            <Tag class="w-5 h-5 text-cyan-600" />
                            <h2>Informações da Categoria</h2>
                        </div>

                        <div class="space-y-6">
                            <div>
                                <Label for="name" class="flex items-center gap-1 text-gray-700 pb-2 font-medium">
                                    Nome da Categoria
                                    <span class="text-red-500">*</span>
                                </Label>
                                <input id="name" v-model="form.name" type="text"
                                    placeholder="Ex: Documentos Pessoais, Dados Médicos, Histórico Familiar..."
                                    :class="inputClass" :disabled="form.processing" />
                                <p v-if="form.errors.name" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div>
                                <Label for="description" class="text-gray-700 pb-2 font-medium">Descrição</Label>
                                <textarea id="description" v-model="form.description"
                                    placeholder="Descreva brevemente o objetivo desta categoria (opcional)..."
                                    :class="inputClass" rows="5" :disabled="form.processing"></textarea>
                                <p v-if="form.errors.description" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.description }}
                                </p>
                                <p class="mt-2 text-xs text-gray-500">
                                    Esta descrição será exibida para ajudar na organização dos formulários.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="space-y-6">
                    <div v-if="isEdit" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                        <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <FileText class="w-4 h-4" />
                            Informações do Registro
                        </h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-500">ID:</span>
                                <span class="font-mono font-medium text-gray-900">#{{ category.id }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-500">Criado em:</span>
                                <span class="text-gray-900">{{ new Date(category.created_at).toLocaleDateString('pt-BR')
                                }}</span>
                            </div>
                            <div class="flex justify-between py-2">
                                <span class="text-gray-500">Atualizado em:</span>
                                <span class="text-gray-900">{{ new Date(category.updated_at).toLocaleDateString('pt-BR')
                                }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 rounded-xl border border-gray-200 p-6">
                        <h3 class="font-semibold text-gray-700 mb-3">Preview</h3>
                        <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-lg bg-cyan-100 flex items-center justify-center flex-shrink-0">
                                    <Tag class="w-5 h-5 text-cyan-600" />
                                </div>
                                <div class="min-w-0">
                                    <h4 class="font-semibold text-gray-900 truncate">
                                        {{ form.name || 'Nome da Categoria' }}
                                    </h4>
                                    <p class="text-sm text-gray-500 line-clamp-2">
                                        {{ form.description || 'Descrição opcional...' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </CentralAdminLayout>
</template>
