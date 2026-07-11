<script setup>
import { computed, ref } from "vue";
import { Head, useForm, router, usePage } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import { Label } from "@/Components/ui/label";
import CentralAdminLayout from "@/Layouts/CentralAdminLayout.vue";
import { showToast } from '@/Utils/toast';
import Breadcrumb from "@/Components/Breadcrumb.vue";
import InputError from "@/Components/InputError.vue";
import PasswordInput from '@/Components/PasswordInput.vue';
import FormSelectorDialog from '@/Components/FormSelectorDialog.vue';
import {
    ArrowLeft,
    Save,
    Building2,
    Home,
    Info,
    AlertCircle,
    Maximize2,
    X,
    Globe,
    Database,
    CheckCircle2,
    ShieldAlert,
    User,
    Mail,
    Lock,
    FileText,
    ListChecks,
    LayoutTemplate,
    UserCog,
    ChevronRight,
    CircleCheck,
    Circle
} from "lucide-vue-next";
const page = usePage();
const auth = computed(() => page.props.authUser || {});
const can = computed(() => page.props.authUser?.can?.paginas || {});
const canManage = computed(() => page.props.authUser?.can?.manage || false);
const props = defineProps({
    tenant: {
        type: Object,
        default: null
    },
    forms: {
        type: Array,
        default: () => []
    }
});
const isEdit = computed(() => !!props.tenant);
const inputClass = "w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all";
const inputErrorClass = "w-full px-3 py-2 border border-red-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 bg-red-50 transition-all";
const inputFilledClass = "w-full px-3 py-2 border border-green-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 bg-green-50/30 transition-all";
const form = useForm({
    descricao: props.tenant?.data?.descricao || '',
    nome: props.tenant?.data?.nome || '',
    email: props.tenant?.data?.email || '',
    senha: '',
    senha_confirmation: '',
    forms: props.tenant?.data?.forms?.map(f => f.id) || [],
});
const dialogOpen = ref(false);
const selectedFormsNames = computed(() => {
    return props.forms
        .filter(f => form.forms.includes(f.id))
        .map(f => f.title);
});
const paginaSectionFilled = computed(() => {
    const hasDescricao = form.descricao.trim().length > 0;
    const hasForms = form.forms.length > 0;
    return { hasDescricao, hasForms, total: [hasDescricao, hasForms].filter(Boolean).length, max: 2 };
});
const adminSectionFilled = computed(() => {
    const hasNome = form.nome.trim().length > 0;
    const hasEmail = form.email.trim().length > 0;
    const hasSenha = isEdit.value ? true : form.senha.length > 0;
    const hasConfirmacao = isEdit.value ? true : form.senha_confirmation.length > 0;
    return {
        hasNome,
        hasEmail,
        hasSenha,
        hasConfirmacao,
        total: [hasNome, hasEmail, hasSenha, hasConfirmacao].filter(Boolean).length,
        max: 4
    };
});
const totalProgress = computed(() => {
    const pagina = paginaSectionFilled.value.total;
    const admin = adminSectionFilled.value.total;
    const total = pagina + admin;
    const max = paginaSectionFilled.value.max + adminSectionFilled.value.max;
    return Math.round((total / max) * 100);
});
const getInputClass = (fieldName, hasValue) => {
    if (form.errors[fieldName]) return inputErrorClass;
    if (hasValue && !form.processing) return inputFilledClass;
    return inputClass;
};
const submit = () => {
    if (isEdit.value) {
        form.put(route('pagina.update', props.tenant.id), {
            preserveScroll: true,
            onSuccess: () => {
                showToast('Registro atualizado com sucesso!', 'success');
            },
            onError: (errors) => {
                const message = Object.values(errors)[0] || 'Erro ao atualizar';
                showToast(message, 'error');
            }
        });
    } else {
        form.post(route('pagina.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showToast('Registro criado com sucesso!', 'success');
                form.reset();
            },
            onError: (errors) => {
                const message = Object.values(errors)[0] || 'Erro ao criar';
                showToast(message, 'error');
            }
        });
    }
};
const goBack = () => {
    router.visit(route('pagina.index'));
};
const breadcrumbs = computed(() => [
    { label: 'Página de Parceiros', href: route('pagina.index'), icon: Home },
    { label: isEdit.value ? 'Editar' : 'Novo', href: null },
]);
</script>
<template>

    <Head :title="isEdit ? 'Editar' : 'Novo'" />
    <CentralAdminLayout>
        <div class="flex flex-wrap items-center justify-between gap-2 mb-6">
            <div class="space-y-1">
                <Breadcrumb v-if="breadcrumbs.length > 0" :items="breadcrumbs" />
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">
                    {{ isEdit ? 'Editar Registro' : 'Novo Registro' }}
                </h1>
            </div>
            <!-- Barra de progresso geral -->
            <div class="flex items-center gap-3">
                <div class="hidden sm:flex items-center gap-2 text-sm text-gray-500">
                    <CircleCheck v-if="totalProgress === 100" class="w-4 h-4 text-green-500" />
                    <Circle v-else class="w-4 h-4 text-gray-300" />
                    <span>{{ totalProgress }}% completo</span>
                </div>
                <Button variant="primary" @click="submit" :disabled="form.processing || !can?.create" class="gap-2">
                    <Save class="w-4 h-4" />
                    <span v-if="form.processing">Salvando...</span>
                    <span v-else>Salvar</span>
                </Button>
            </div>
        </div>
        <div class="space-y-6 mx-auto ">
            <!-- Alerta de Erros -->
            <div v-if="Object.keys(form.errors).length > 0" class="bg-red-50 border border-red-200 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <AlertCircle class="w-5 h-5 text-red-600 mt-0.5" />
                    <div>
                        <h3 class="font-semibold text-red-900 mb-1">Corrija os erros abaixo:</h3>
                        <ul class="text-sm text-red-800 space-y-1">
                            <li v-for="(error, key) in form.errors" :key="key">
                                <strong>{{ key }}:</strong> {{ error }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Formulário -->
            <div class="space-y-6">
                <!-- ═══════════════════════════════════════
                     SEÇÃO 1: INFORMAÇÕES DA PÁGINA
                     ═══════════════════════════════════════ -->
                <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                    <!-- Header da seção -->
                    <div class="px-6 py-4 bg-gray-50/80 border-b border-gray-200 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-cyan-100 rounded-lg">
                                <LayoutTemplate class="w-5 h-5 text-cyan-600" />
                            </div>
                            <div>
                                <h2 class="font-semibold text-gray-900">Informações da Página</h2>
                                <p class="text-xs text-gray-500">Dados de identificação e formulários vinculados</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 text-xs font-medium">
                            <span
                                :class="paginaSectionFilled.total === paginaSectionFilled.max ? 'text-green-600' : 'text-gray-400'">
                                {{ paginaSectionFilled.total }}/{{ paginaSectionFilled.max }}
                            </span>
                            <CircleCheck v-if="paginaSectionFilled.total === paginaSectionFilled.max"
                                class="w-4 h-4 text-green-500" />
                            <Circle v-else class="w-4 h-4 text-gray-300" />
                        </div>
                    </div>
                    <div class="p-6 space-y-5">
                        <!-- Descrição -->
                        <div>
                            <Label for="descricao" class="text-gray-700 font-medium flex items-center gap-2 pb-2">
                                <FileText class="w-4 h-4 text-gray-400" />
                                Descrição da página
                                <span class="text-red-500">*</span>
                                <CircleCheck v-if="paginaSectionFilled.hasDescricao"
                                    class="w-4 h-4 text-green-500 ml-auto" title="Preenchido" />
                            </Label>
                            <input id="descricao" v-model="form.descricao" type="text"
                                placeholder="Informe a descrição da página"
                                :class="getInputClass('descricao', paginaSectionFilled.hasDescricao)"
                                :disabled="form.processing" />
                            <InputError :message="form.errors.descricao" class="mt-1" />
                            <p class="mt-1 text-xs text-gray-500">
                                Descrição obrigatória. Não pode conter caracteres especiais nem números.
                            </p>
                        </div>
                        <!-- Selector de Formulários -->
                        <div>
                            <Label class="text-gray-700 font-medium flex items-center gap-2 pb-2">
                                <ListChecks class="w-4 h-4 text-gray-400" />
                                Formulários vinculados
                                <CircleCheck v-if="paginaSectionFilled.hasForms" class="w-4 h-4 text-green-500 ml-auto"
                                    title="Preenchido" />
                            </Label>
                            <div @click="dialogOpen = true" :class="[
                                'w-full px-3 py-2.5 border rounded-md cursor-pointer transition-all flex items-center justify-between min-h-[42px]',
                                form.errors.forms ? 'border-red-300 bg-red-50' :
                                    paginaSectionFilled.hasForms ? 'border-green-300 bg-green-50/30 hover:border-green-400' :
                                        'border-gray-300 hover:border-cyan-400'
                            ]">
                                <div class="flex flex-wrap gap-1.5">
                                    <span v-if="selectedFormsNames.length === 0" class="text-gray-400 text-sm">
                                        Clique para selecionar formulários...
                                    </span>
                                    <span v-for="name in selectedFormsNames" :key="name"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-cyan-100 text-cyan-800 border border-cyan-200">
                                        <CheckCircle2 class="w-3 h-3" />
                                        {{ name }}
                                    </span>
                                </div>
                                <ListChecks class="w-4 h-4 text-gray-400 flex-shrink-0" />
                            </div>
                            <InputError :message="form.errors.forms" class="mt-1" />
                            <p class="mt-1 text-xs text-gray-500">
                                Selecione um ou mais formulários para vincular à página.
                            </p>
                        </div>
                    </div>
                </div>
                <!-- ═══════════════════════════════════════
                     SEÇÃO 2: ACESSO ADMINISTRATIVO
                     ═══════════════════════════════════════ -->
                <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                    <!-- Header da seção -->
                    <div class="px-6 py-4 bg-gray-50/80 border-b border-gray-200 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-purple-100 rounded-lg">
                                <UserCog class="w-5 h-5 text-purple-600" />
                            </div>
                            <div>
                                <h2 class="font-semibold text-gray-900">Acesso Administrativo</h2>
                                <p class="text-xs text-gray-500">Credenciais do usuário admin da página</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 text-xs font-medium">
                            <span
                                :class="adminSectionFilled.total === adminSectionFilled.max ? 'text-green-600' : 'text-gray-400'">
                                {{ adminSectionFilled.total }}/{{ adminSectionFilled.max }}
                            </span>
                            <CircleCheck v-if="adminSectionFilled.total === adminSectionFilled.max"
                                class="w-4 h-4 text-green-500" />
                            <Circle v-else class="w-4 h-4 text-gray-300" />
                        </div>
                    </div>
                    <div class="p-6 space-y-5">
                        <!-- Nome -->
                        <div>
                            <Label for="nome" class="text-gray-700 font-medium flex items-center gap-2 pb-2">
                                <Building2 class="w-4 h-4 text-gray-400" />
                                Nome do administrador
                                <span class="text-red-500">*</span>
                                <CircleCheck v-if="adminSectionFilled.hasNome" class="w-4 h-4 text-green-500 ml-auto"
                                    title="Preenchido" />
                            </Label>
                            <input id="nome" v-model="form.nome" type="text"
                                placeholder="Nome completo do administrador"
                                :class="getInputClass('nome', adminSectionFilled.hasNome)"
                                :disabled="form.processing" />
                            <InputError :message="form.errors.nome" class="mt-1" />
                        </div>
                        <!-- E-mail -->
                        <div>
                            <Label for="email" class="text-gray-700 font-medium flex items-center gap-2 pb-2">
                                <Mail class="w-4 h-4 text-gray-400" />
                                E-mail
                                <span class="text-red-500">*</span>
                                <CircleCheck v-if="adminSectionFilled.hasEmail" class="w-4 h-4 text-green-500 ml-auto"
                                    title="Preenchido" />
                            </Label>
                            <input id="email" v-model="form.email" type="email" placeholder="admin@exemplo.com"
                                :class="getInputClass('email', adminSectionFilled.hasEmail)"
                                :disabled="form.processing" />
                            <InputError :message="form.errors.email" class="mt-1" />
                        </div>
                        <!-- Senha -->
                        <PasswordInput v-model="form.senha" id="senha" label="Senha" placeholder="••••••••"
                            :error="form.errors.senha" :required="!isEdit" :disabled="form.processing"
                            :showOptionalLabel="isEdit"
                            :inputClass="getInputClass('senha', adminSectionFilled.hasSenha)"
                            :inputErrorClass="inputErrorClass">
                            <template #hint>
                                <p v-if="!isEdit" class="mt-1 text-xs text-gray-500">
                                    Mínimo 8 caracteres, com letra maiúscula, minúscula e número.
                                </p>
                            </template>
                        </PasswordInput>
                        <!-- Confirmar Senha -->
                        <PasswordInput v-if="!isEdit" v-model="form.senha_confirmation" id="senha_confirmation"
                            label="Confirmar Senha" placeholder="••••••••" :error="form.errors.senha_confirmation"
                            required :disabled="form.processing"
                            :inputClass="getInputClass('senha_confirmation', adminSectionFilled.hasConfirmacao)"
                            :inputErrorClass="inputErrorClass" />
                    </div>
                </div>
            </div>
        </div>
        <FormSelectorDialog v-model:open="dialogOpen" v-model="form.forms" :forms="props.forms"
            @confirm="(selected) => console.log('Selecionados:', selected)" />
    </CentralAdminLayout>
</template>
