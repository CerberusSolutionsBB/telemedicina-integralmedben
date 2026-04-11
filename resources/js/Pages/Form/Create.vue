<script setup>
import { ref, computed } from "vue";
import { Head, router } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import { Label } from "@/Components/ui/label";
import CentralAdminLayout from "@/Layouts/CentralAdminLayout.vue";
import { showToast } from '@/Utils/toast';
import Breadcrumb from "@/Components/Breadcrumb.vue";
import FormField from "@/Components/FormFields/FormField.vue";
import ColorPicker from "@/Components/ColorPicker.vue";
import ImageUpload from '@/Components/ImageUpload.vue';

import {
    Plus,
    Trash2,
    GripVertical,
    Settings,
    Eye,
    Save,
    Calendar,
    Globe,
    Lock,
    ChevronDown,
    Type,
    List,
    CheckSquare,
    Radio,
    CalendarDays,
    Mail,
    Hash,
    Home,
    Tag,
    Scale,
    Palette, Paintbrush, IdCard
} from "lucide-vue-next";

const breadcrumbs = computed(() => [
    { label: 'Início', href: route('dashboard'), icon: Home },
    { label: 'Formulários', href: route('forms.index') },
    isEdit.value
        ? { label: 'Editar', href: null }
        : { label: 'Novo Formulário', href: null },
]);

const props = defineProps({
    form: {
        type: Object,
        default: null
    },
    statusOptions: {
        type: Array,
        default: () => []
    },
    categorias: {
        type: Array,
        default: () => []
    },
    leis: {
        type: Array,
        default: () => []
    },
    credencias_clubles: {
        type: Array,
        default: () => []
    },
    can: {
        type: Object,
        default: () => ({})
    }
});

const isEdit = computed(() => !!props.form);

const inputClass = "w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-500";

const formData = ref({
    title: props.form?.title || '',
    description: props.form?.description || '',
    categoria_id: props.form?.categoria_id || null,
    lei_id: props.form?.lei_id || null,
    status: props.form?.status || 'rascunho',
    is_public: props.form?.is_public || false,
    published_at: props.form?.published_at || '',
    expires_at: props.form?.expires_at || '',
    response_limit: props.form?.response_limit || '',
    primary_color: props.form?.primary_color || '#22d3ee',
    secondary_color: props.form?.secondary_color || '#06b6d4',
    btn_confirmar_descricao: props.form?.btn_confirmar_descricao ?? 'ENVIAR CADASTRO',
    sub_descricao: props.form?.sub_descricao ?? null,
    observacao: props.form?.observacao ?? null,
    credencia_cluble_id: props.form?.credencia_cluble_id ?? null,
    logo: null,
    logo_url: props.form?.logo_url || null,
    logo_posicao: props.form?.logo_posicao || 'centro',
    settings: props.form?.settings || {
        allow_multiple: false,
        show_progress: true,
        theme: 'default'
    },
    fields: props.form?.fields || []
});

const fieldTypes = [
    { value: 'text', label: 'Texto curto', icon: Type },
    { value: 'textarea', label: 'Texto longo', icon: Type },
    { value: 'email', label: 'E-mail', icon: Mail },
    { value: 'number', label: 'Número', icon: Hash },
    { value: 'date', label: 'Data', icon: CalendarDays },
    { value: 'select', label: 'Seleção única', icon: Radio },
    { value: 'checkbox', label: 'Múltipla escolha', icon: CheckSquare },
    { value: 'radio', label: 'Opções (radio)', icon: List },
];

const selectedCategoria = computed(() => {
    return props.categorias.find(c => c.value === formData.value.categoria_id);
});

const selectedLei = computed(() => {
    return props.leis.find(l => l.value === formData.value.lei_id);
});

const selectedCredencial = computed(() => {
    return props.credencias_clubles.find(c => c.value === formData.value.credencia_cluble_id);
});

const addField = (type = 'text') => {
    const newField = {
        id: Date.now(),
        type: type,
        label: '',
        placeholder: '',
        required: false,
        options: ['select', 'checkbox', 'radio'].includes(type) ? ['Opção 1', 'Opção 2'] : [],
        help_text: '',
        order: formData.value.fields.length
    };
    formData.value.fields.push(newField);
};

const removeField = (index) => {
    if (confirm('Tem certeza que deseja remover este campo?')) {
        formData.value.fields.splice(index, 1);
        formData.value.fields.forEach((field, idx) => field.order = idx);
    }
};

const addOption = (fieldIndex) => {
    const field = formData.value.fields[fieldIndex];
    field.options.push(`Opção ${field.options.length + 1}`);
};

const removeOption = (fieldIndex, optionIndex) => {
    const field = formData.value.fields[fieldIndex];
    if (field.options.length > 1) field.options.splice(optionIndex, 1);
};

const moveField = (index, direction) => {
    const fields = formData.value.fields;
    const newIndex = direction === 'up' ? index - 1 : index + 1;
    if (newIndex >= 0 && newIndex < fields.length) {
        [fields[index], fields[newIndex]] = [fields[newIndex], fields[index]];
        fields.forEach((field, idx) => field.order = idx);
    }
};

const saving = ref(false);

const saveForm = (publish = false) => {
    saving.value = true;

    if (publish) {
        formData.value.status = 'ativo';
        formData.value.published_at = new Date().toISOString();
    }

    const routeName = isEdit.value ? 'forms.update' : 'forms.store';
    const routeParams = isEdit.value ? { form: props.form.id } : {};

    const payload = new FormData();

    Object.keys(formData.value).forEach(key => {
        const value = formData.value[key];

        if (key === 'fields') {
            payload.append(key, JSON.stringify(value || []));
        } else if (key === 'settings') {
            const settingsValue = value && typeof value === 'object' ? value : {};
            payload.append(key, JSON.stringify(settingsValue));
        } else if (key === 'logo' && value instanceof File) {
            payload.append(key, value);
        } else if (key === 'logo_posicao') {
            if (formData.value.logo || formData.value.logo_url) {
                payload.append(key, value || 'centro');
            }
        } else if (!['logo_url'].includes(key)) {
            payload.append(key, value !== null && value !== undefined ? value : '');
        }
    });

    if (isEdit.value) {
        payload.append('_method', 'PUT');
    }

    router.post(route(routeName, routeParams), payload, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            showToast('Formulário salvo com sucesso!', 'success');
        },
        onError: (errors) => {
            console.error('Erros de validação:', errors);
            let errorMessages = [];
            if (errors.title) errorMessages.push(errors.title);
            if (errors.status) errorMessages.push(errors.status);
            if (errors.categoria_id) errorMessages.push(errors.categoria_id);
            if (errors.lei_id) errorMessages.push(errors.lei_id);
            if (errors.fields) errorMessages.push(errors.fields);
            if (errors.settings) errorMessages.push(errors.settings);

            Object.keys(errors).forEach(key => {
                if (key.startsWith('fields.')) {
                    const fieldErrors = Array.isArray(errors[key]) ? errors[key] : [errors[key]];
                    errorMessages.push(...fieldErrors);
                }
            });

            const message = errorMessages.length > 0
                ? errorMessages[0]
                : 'Erro ao salvar formulário. Verifique os campos.';

            showToast(message, 'error');
        },
        onFinish: () => {
            saving.value = false;
        }
    });
};

const showPreview = ref(false);
</script>

<template>

    <Head :title="isEdit ? 'Editar Formulário' : 'Criar Formulário'" />

    <CentralAdminLayout>
        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-2 mb-6">
            <div class="space-y-1">
                <Breadcrumb :items="breadcrumbs" />
                <h1 class="text-xl sm:text-2xl font-bold">
                    {{ isEdit ? 'Editar Formulário' : 'Novo Formulário' }}
                </h1>
                <p class="text-sm text-gray-500">
                    Configure as informações e adicione os campos desejados
                </p>
            </div>

            <div class="flex gap-2">
                <Button variant="outline" @click="showPreview = !showPreview" class="gap-2">
                    <Eye class="w-4 h-4" />
                    {{ showPreview ? 'Editar' : 'Preview' }}
                </Button>

                <Button variant="outline" @click="saveForm(false)" :disabled="saving" class="gap-2">
                    <Save class="w-4 h-4" />
                    Salvar Rascunho
                </Button>

                <Button variant="primary" @click="saveForm(true)" :disabled="saving || !formData.title" class="gap-2">
                    <Globe class="w-4 h-4" />
                    Publicar
                </Button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Editor -->
            <div v-if="!showPreview" class="lg:col-span-2 space-y-6">
                <!-- Informações Básicas -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                    <div class="flex items-center gap-2 mb-4 text-gray-900 font-semibold">
                        <Settings class="w-5 h-5" />
                        <h2>Informações do Formulário</h2>
                    </div>

                    <div class="space-y-4">
                        <!-- Título -->
                        <div>
                            <Label for="title" class="flex items-center gap-1 text-gray-700 pb-1">
                                Título
                                <span class="text-red-500">*</span>
                            </Label>
                            <input id="title" v-model="formData.title" placeholder="Ex: Pesquisa de Satisfação"
                                :class="inputClass" required />
                        </div>
                        <div>
                            <Label for="description" class="text-gray-700">Descrição</Label>
                            <textarea id="description" v-model="formData.description"
                                placeholder="Descreva o objetivo deste formulário..." :class="inputClass"
                                rows="3"></textarea>
                        </div>
                        <!-- Campos do Botão e Descrições -->
                        <div class="space-y-4">
                            <!-- Texto do Botão Confirmar -->
                            <div>
                                <Label for="btn_confirmar_descricao" class="flex items-center gap-1 text-gray-700 pb-1">
                                    Texto do Botão de Confirmação
                                    <span class="text-red-500">*</span>
                                </Label>
                                <input id="btn_confirmar_descricao" v-model="formData.btn_confirmar_descricao"
                                    placeholder="Ex: ENVIAR CADASTRO, CONFIRMAR, SALVAR" :class="inputClass" />
                            </div>

                            <!-- Sub Descrição -->
                            <div>
                                <Label for="sub_descricao" class="flex items-center gap-1 text-gray-700 pb-1">
                                    Sub Descrição
                                </Label>
                                <input id="sub_descricao" v-model="formData.sub_descricao"
                                    placeholder="Ex: Preencha todos os campos obrigatórios" :class="inputClass" />
                            </div>
                        </div>
                        <div>
                            <ImageUpload v-model="formData.logo" v-model:posicao="formData.logo_posicao"
                                label="Logo do Formulário"
                                description="Arraste uma imagem ou clique para selecionar o logo"
                                :preview-url="formData.logo_url" @error="(err) => showToast(err, 'error')" />
                        </div>

                        <!-- Categoria, Credencial e Lei -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <Label for="categoria" class="flex items-center gap-2 text-gray-700 pb-1">
                                    <Tag class="w-4 h-4" />
                                    Categoria
                                </Label>
                                <select id="categoria" v-model="formData.categoria_id" :class="inputClass">
                                    <option :value="null">Selecione uma categoria</option>
                                    <option v-for="categoria in categorias" :key="categoria.value"
                                        :value="categoria.value">
                                        {{ categoria.label }}
                                    </option>
                                </select>
                                <p v-if="selectedCategoria?.description" class="text-xs text-gray-500 mt-1">
                                    {{ selectedCategoria.description }}
                                </p>
                            </div>

                            <!-- Credencial Clube -->
                            <div>
                                <Label for="credencial_clube" class="flex items-center gap-2 text-gray-700 pb-1">
                                    <IdCard class="w-4 h-4" />
                                    Credencial Clube
                                </Label>
                                <select id="credencial_clube" v-model="formData.credencia_cluble_id"
                                    :class="inputClass">
                                    <option :value="null">Selecione uma credencial</option>
                                    <option v-for="credencial in credencias_clubles" :key="credencial.value"
                                        :value="credencial.value">
                                        {{ credencial.label }}
                                    </option>
                                </select>
                                <p v-if="selectedCredencial?.description" class="text-xs text-gray-500 mt-1">
                                    {{ selectedCredencial.description }}
                                </p>
                            </div>

                            <div>
                                <Label for="lei" class="flex items-center gap-2 text-gray-700 pb-1">
                                    <Scale class="w-4 h-4" />
                                    Base Legal (Lei)
                                </Label>
                                <select id="lei" v-model="formData.lei_id" :class="inputClass">
                                    <option :value="null">Selecione uma lei</option>
                                    <option v-for="lei in leis" :key="lei.value" :value="lei.value">
                                        {{ lei.label }}
                                    </option>
                                </select>
                                <p v-if="selectedLei" class="text-xs text-gray-500 mt-1">
                                    Tipo: {{ selectedLei.type }}
                                </p>
                            </div>
                        </div>

                        <!-- Status e Limite -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <Label for="status" class="text-gray-700">Status</Label>
                                <select id="status" v-model="formData.status" :class="inputClass">
                                    <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <Label for="response_limit" class="text-gray-700">Limite de Respostas</Label>
                                <input id="response_limit" type="number" v-model="formData.response_limit"
                                    placeholder="Ilimitado" :class="inputClass" />
                            </div>
                        </div>

                        <!-- Datas -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <Label for="published_at" class="text-gray-700 flex items-center gap-2">
                                    <Calendar class="w-4 h-4" />
                                    Data de Publicação
                                </Label>
                                <input id="published_at" type="datetime-local" v-model="formData.published_at"
                                    :class="inputClass" />
                            </div>

                            <div>
                                <Label for="expires_at" class="text-gray-700 flex items-center gap-2">
                                    <Calendar class="w-4 h-4" />
                                    Data de Expiração
                                </Label>
                                <input id="expires_at" type="datetime-local" v-model="formData.expires_at"
                                    :class="inputClass" />
                            </div>
                        </div>

                        <!-- Cores -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <Label for="primary_color" class="text-gray-700 flex items-center gap-2">
                                    <Palette class="w-4 h-4" />
                                    Cor Primária
                                </Label>
                                <ColorPicker id="primary_color" v-model="formData.primary_color"
                                    description="Cor principal do formulário, usada em botões e destaques" />
                            </div>

                            <div>
                                <Label for="secondary_color" class="text-gray-700 flex items-center gap-2">
                                    <Paintbrush class="w-4 h-4" />
                                    Cor Secundária
                                </Label>
                                <ColorPicker id="secondary_color" v-model="formData.secondary_color"
                                    description="Cor secundária para detalhes e elementos de fundo" />
                            </div>
                        </div>

                        <!-- Visibilidade -->
                        <div class="border-t pt-4 mt-4">
                            <div class="flex items-center gap-3">
                                <input type="checkbox" id="is_public" v-model="formData.is_public"
                                    class="w-5 h-5 rounded border-gray-300 text-cyan-600 focus:ring-cyan-500 cursor-pointer" />
                                <div>
                                    <Label for="is_public"
                                        class="mb-0 cursor-pointer font-medium text-gray-700 flex items-center gap-2">
                                        <Globe v-if="formData.is_public" class="w-4 h-4 text-green-600" />
                                        <Lock v-else class="w-4 h-4 text-gray-400" />
                                        Formulário Público
                                    </Label>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ formData.is_public
                                            ? 'Qualquer pessoa com o link pode responder'
                                            : 'Apenas usuários logados podem ver e responder'
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Campos Dinâmicos -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">Campos do Formulário</h2>
                        <span class="text-sm text-gray-500">
                            {{ formData.fields.length }} campo(s)
                        </span>
                    </div>

                    <div v-if="formData.fields.length === 0"
                        class="text-center py-12 bg-gray-50 rounded-xl border-2 border-dashed border-gray-300">
                        <p class="text-gray-500 mb-4">Nenhum campo adicionado ainda</p>
                        <Button variant="outline" @click="addField('text')" class="gap-2">
                            <Plus class="w-4 h-4" />
                            Adicionar primeiro campo
                        </Button>
                    </div>

                    <div v-for="(field, index) in formData.fields" :key="field.id"
                        class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 relative group">
                        <div
                            class="absolute right-4 top-4 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button @click="moveField(index, 'up')" :disabled="index === 0"
                                class="p-1 hover:bg-gray-100 rounded disabled:opacity-30">
                                <ChevronDown class="w-4 h-4 rotate-180" />
                            </button>
                            <button @click="moveField(index, 'down')" :disabled="index === formData.fields.length - 1"
                                class="p-1 hover:bg-gray-100 rounded disabled:opacity-30">
                                <ChevronDown class="w-4 h-4" />
                            </button>
                            <button @click="removeField(index)" class="p-1 hover:bg-red-50 text-red-600 rounded">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="mt-2 text-gray-400">
                                <GripVertical class="w-5 h-5" />
                            </div>

                            <div class="flex-1 space-y-4">
                                <div class="flex items-center gap-3">
                                    <select v-model="field.type" :class="inputClass + ' w-auto'">
                                        <option v-for="type in fieldTypes" :key="type.value" :value="type.value">
                                            {{ type.label }}
                                        </option>
                                    </select>

                                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                                        <input type="checkbox" v-model="field.required"
                                            class="w-5 h-5 rounded border-gray-300 text-cyan-600 focus:ring-cyan-500" />
                                        <span class="text-gray-700">Obrigatório</span>
                                    </label>
                                </div>

                                <input v-model="field.label" placeholder="Digite a pergunta"
                                    :class="inputClass + ' font-medium text-lg'" />

                                <div class="grid grid-cols-2 gap-4">
                                    <input v-model="field.placeholder" placeholder="Texto de ajuda (placeholder)"
                                        :class="inputClass" />
                                    <input v-model="field.help_text" placeholder="Descrição adicional"
                                        :class="inputClass" />
                                </div>

                                <!-- Opções -->
                                <div v-if="['select', 'checkbox', 'radio'].includes(field.type)"
                                    class="space-y-2 pl-4 border-l-2 border-gray-200">
                                    <p class="text-sm text-gray-600 font-medium">Opções:</p>
                                    <div v-for="(option, optIndex) in field.options" :key="optIndex"
                                        class="flex items-center gap-2">
                                        <input v-model="field.options[optIndex]" :class="inputClass" />
                                        <button @click="removeOption(index, optIndex)"
                                            class="p-1 text-red-500 hover:bg-red-50 rounded"
                                            :disabled="field.options.length <= 1">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                    <Button variant="ghost" size="sm" @click="addOption(index)"
                                        class="gap-2 text-cyan-600">
                                        <Plus class="w-4 h-4" />
                                        Adicionar opção
                                    </Button>
                                </div>

                                <!-- Preview -->
                                <div class="mt-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    <p class="text-xs text-gray-500 mb-2">Preview:</p>
                                    <FormField :field="field" disabled />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botão flutuante -->
                <div v-if="formData.fields.length > 0" class="flex justify-center pt-4">
                    <div class="relative group">
                        <Button variant="primary" size="lg" @click="addField('text')"
                            class="rounded-full shadow-lg gap-2 px-6">
                            <Plus class="w-5 h-5" />
                            Adicionar Campo
                        </Button>

                        <div
                            class="absolute top-full left-1/2 -translate-x-1/2 mt-2 bg-white rounded-lg shadow-xl border border-gray-200 p-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-10 min-w-[200px]">
                            <p class="text-xs text-gray-500 px-2 py-1">Tipos de campo:</p>
                            <button v-for="type in fieldTypes" :key="type.value" @click="addField(type.value)"
                                class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md text-left">
                                <component :is="type.icon" class="w-4 h-4" />
                                {{ type.label }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preview Completo -->
            <div v-else class="lg:col-span-2">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 max-w-2xl mx-auto">
                    <div class="border-b pb-6 mb-6">
                        <!-- Logo no Preview com posição -->
                        <div v-if="formData.logo_url" class="mb-4" :class="{
                            'text-left': formData.logo_posicao === 'esquerda',
                            'text-center': formData.logo_posicao === 'centro',
                            'text-right': formData.logo_posicao === 'direita'
                        }">
                            <img :src="formData.logo_url" alt="Logo" class="h-16 object-contain inline-block" />
                        </div>

                        <h1 class="text-2xl font-bold text-gray-900 mb-2">
                            {{ formData.title || 'Sem título' }}
                        </h1>
                        <p class="text-gray-600">
                            {{ formData.description || 'Sem descrição' }}
                        </p>

                        <!-- Info da Categoria, Credencial e Lei no Preview -->
                        <div v-if="selectedCategoria || selectedLei || selectedCredencial"
                            class="flex flex-wrap gap-2 mt-3">
                            <span v-if="selectedCategoria"
                                class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                                <Tag class="w-3 h-3" />
                                {{ selectedCategoria.label }}
                            </span>
                            <span v-if="selectedCredencial"
                                class="inline-flex items-center gap-1 px-2 py-1 bg-orange-100 text-orange-800 text-xs rounded-full">
                                <IdCard class="w-3 h-3" />
                                {{ selectedCredencial.label }}
                            </span>
                            <span v-if="selectedLei"
                                class="inline-flex items-center gap-1 px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded-full">
                                <Scale class="w-3 h-3" />
                                {{ selectedLei.label }}
                            </span>
                        </div>

                        <div class="flex items-center gap-4 mt-4 text-sm text-gray-500">
                            <span v-if="formData.is_public" class="flex items-center gap-1 text-green-600">
                                <Globe class="w-4 h-4" /> Público
                            </span>
                            <span v-else class="flex items-center gap-1">
                                <Lock class="w-4 h-4" /> Privado
                            </span>
                        </div>
                    </div>

                    <form class="space-y-6" @submit.prevent>
                        <FormField v-for="field in formData.fields" :key="field.id" :field="field" disabled />

                        <div v-if="formData.fields.length === 0" class="text-center text-gray-500 py-8">
                            Nenhum campo adicionado
                        </div>

                        <div class="mt-8 pt-6 border-t flex justify-end">
                            <Button type="submit" disabled>Enviar</Button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="hidden lg:block space-y-6">
                <div class="bg-blue-50 rounded-xl border border-blue-200 p-4">
                    <h3 class="font-semibold text-blue-900 mb-2">Dicas</h3>
                    <ul class="text-sm text-blue-800 space-y-2 list-disc list-inside">
                        <li>Use campos obrigatórios para garantir dados essenciais</li>
                        <li>Adicione descrições claras para evitar dúvidas</li>
                        <li>Teste o formulário antes de publicar</li>
                        <li>Defina uma data de expiração se for temporário</li>
                    </ul>
                </div>
            </div>
        </div>
    </CentralAdminLayout>
</template>
