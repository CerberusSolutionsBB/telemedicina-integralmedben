<script setup>
import { computed } from "vue";
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
    { label: 'Editar Categoria', href: null },
]);

const props = defineProps({
    category: {
        type: Object,
        required: true
    },
    errors: Object // Recebe erros do backend
});

const inputClass = "w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-500";
const inputErrorClass = "border-red-500 focus:border-red-500 focus:ring-red-500";

const form = useForm({
    name: props.category.name || '',
    description: props.category.description || '',
    slug: props.category.slug || ''
});

const submit = () => {
    if (!props.category?.id) {
        showToast('Erro: Nenhuma categoria selecionada', 'error');
        return;
    }

    form.put(route('configuracoes.categories.forms.update', { id: props.category.id }), {
        preserveScroll: true,
        onSuccess: () => {
            showToast('Categoria atualizada com sucesso!', 'success');
        },
        // Removemos onError para deixar o Inertia lidar automaticamente
        // Os erros ficarão disponíveis em form.errors
    });
};

const goBack = () => {
    router.visit(route('configuracoes.categories.forms.index'));
};
</script>

<template>

    <Head title="Editar Categoria" />

    <CentralAdminLayout>
        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-2 mb-6">
            <div class="space-y-1">
                <Breadcrumb :items="breadcrumbs" />
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">
                    Editar Categoria
                </h1>
                <p class="text-sm text-gray-500">
                    Atualize as informações da categoria <span class="font-mono text-cyan-600">#{{ category.id }}</span>
                </p>
            </div>

            <div class="flex gap-2">
                <Button variant="outline" @click="goBack" class="gap-2">
                    <ArrowLeft class="w-4 h-4" />
                    Voltar
                </Button>

                <Button variant="primary" @click="submit" :disabled="form.processing || !form.name" class="gap-2">
                    <Save class="w-4 h-4" />
                    <span v-if="form.processing">Atualizando...</span>
                    <span v-else>Atualizar</span>
                </Button>
            </div>
        </div>

        <div class="space-y-6 mx-auto">

            <!-- Alerta de Erro Geral -->
            <div v-if="Object.keys(form.errors).length > 0" class="bg-red-50 border border-red-200 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <div class="text-red-600 mt-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
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

            <!-- Dicas -->
            <div class="bg-blue-50 rounded-xl border border-blue-200 p-4">
                <div class="flex items-start gap-3">
                    <Info class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" />
                    <div>
                        <h3 class="font-semibold text-blue-900 mb-1">Dicas de Edição</h3>
                        <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
                            <li>Altere apenas os campos necessários</li>
                            <li>Verifique as informações antes de salvar</li>
                            <li>A descrição ajuda na organização dos formulários</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Formulário -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                        <div
                            class="flex items-center gap-2 mb-6 text-gray-900 font-semibold border-b border-gray-100 pb-4">
                            <Tag class="w-5 h-5 text-cyan-600" />
                            <h2>Informações da Categoria</h2>
                        </div>

                        <div class="space-y-6">
                            <!-- Campo Nome -->
                            <div>
                                <Label for="name" class="flex items-center gap-1 text-gray-700 pb-2 font-medium">
                                    Nome da Categoria
                                    <span class="text-red-500">*</span>
                                </Label>
                                <input id="name" v-model="form.name" type="text" placeholder="Nome da categoria"
                                    :class="[inputClass, form.errors.name ? inputErrorClass : '']"
                                    :disabled="form.processing" />
                                <p v-if="form.errors.name" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <!-- Campo Descrição -->
                            <div>
                                <Label for="description" class="text-gray-700 pb-2 font-medium">Descrição</Label>
                                <textarea id="description" v-model="form.description"
                                    placeholder="Descrição opcional..."
                                    :class="[inputClass, form.errors.description ? inputErrorClass : '']" rows="5"
                                    :disabled="form.processing"></textarea>
                                <p v-if="form.errors.description" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.description }}
                                </p>
                            </div>

                            <!-- Campo Slug (se quiser editar manualmente) -->
                            <div v-if="form.errors.slug">
                                <Label class="flex items-center gap-1 text-gray-700 pb-2 font-medium">
                                    Slug (Identificador)
                                </Label>

                                <input v-model="form.slug" type="text" :class="[inputClass, inputErrorClass]"
                                    disabled />
                                <p class="mt-2 text-sm text-red-600">
                                    {{ form.errors.slug }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    Este erro ocorre porque já existe outra categoria com o mesmo nome/slug.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
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

                    <!-- Preview -->
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
                                        {{ form.description || 'Sem descrição' }}
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
