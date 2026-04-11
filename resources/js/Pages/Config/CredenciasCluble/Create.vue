<script setup>
import { computed, ref } from "vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import { Label } from "@/Components/ui/label";
import CentralAdminLayout from "@/Layouts/CentralAdminLayout.vue";
import { showToast } from '@/Utils/toast';
import Breadcrumb from "@/Components/Breadcrumb.vue";
import {
    LayoutDashboard,
    Settings,
    BookMarked,
    Building2,
    ClipboardList,
    Users,
    MessageSquare,
    ScrollText,
    LandmarkIcon,
    BarChart3,
    LogOut,
    ChevronRight,
    ChevronDown,
    ArrowLeft,
    Save,
    Shield,
    Key,
    Lock,
    Eye,
    EyeOff,
    FileText,
    Info,
    AlertTriangle,
    Tag
} from "lucide-vue-next";

const breadcrumbs = computed(() => [
    { label: 'Início', href: route('dashboard'), icon: LayoutDashboard },
    { label: 'Configurações', href: null, icon: Settings },
    { label: 'Credenciais Clubles', href: route('configuracoes.credencias_cluble.index'), icon: Shield },
    { label: isEdit.value ? 'Editar Credencial' : 'Nova Credencial', href: null, icon: Key },
]);

const props = defineProps({
    credencial: {
        type: Object,
        default: null
    },
    errors: Object
});

const isEdit = computed(() => !!props.credencial);

const inputClass = "w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-500";
const inputErrorClass = "w-full px-3 py-2 border border-red-500 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500";

const form = useForm({
    title: props.credencial?.title || '',
    grant_type: props.credencial?.grant_type || 'client_credentials',
    client_id: props.credencial?.client_id || '',
    client_secret: props.credencial?.client_secret || '',
    scope: props.credencial?.scope || '*',
});

const showSecret = ref(false);

const submit = () => {
    const routeName = isEdit.value
        ? 'configuracoes.credencias_cluble.update'
        : 'configuracoes.credencias_cluble.store';

    const routeParams = isEdit.value
        ? { credencias_cluble: props.credencial.id }
        : {};

    const method = isEdit.value ? 'put' : 'post';

    form[method](route(routeName, routeParams), {
        preserveScroll: true,
        onSuccess: () => {
            showToast(
                isEdit.value ? 'Credencial atualizada!' : 'Credencial criada e autenticada!',
                'success'
            );
        },
        onError: (errors) => {
            const message = errors.api_error || errors.client_id || 'Erro ao salvar';
            showToast(message, 'error');
        }
    });
};

const goBack = () => {
    router.visit(route('configuracoes.credencias_cluble.index'));
};
</script>

<template>

    <Head :title="isEdit ? 'Editar Credencial' : 'Nova Credencial'" />

    <CentralAdminLayout>
        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-2 mb-6">
            <div class="space-y-1">
                <Breadcrumb :items="breadcrumbs" />
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">
                    {{ isEdit ? 'Editar Credencial' : 'Nova Credencial Cluble' }}
                </h1>
                <p class="text-sm text-gray-500">
                    {{ isEdit
                        ? 'Atualize as credenciais de acesso'
                        : 'Configure as credenciais OAuth - o sistema autenticará automaticamente'
                    }}
                </p>
            </div>

            <div class="flex gap-2">
                <Button variant="outline" @click="goBack" class="gap-2">
                    <ArrowLeft class="w-4 h-4" />
                    Voltar
                </Button>

                <Button variant="primary" @click="submit" :disabled="form.processing || !form.client_id" class="gap-2">
                    <Save class="w-4 h-4" />
                    <span v-if="form.processing">{{ isEdit ? 'Salvando...' : 'Autenticando...' }}</span>
                    <span v-else>{{ isEdit ? 'Atualizar' : 'Salvar & Autenticar' }}</span>
                </Button>
            </div>
        </div>

        <!-- Erro da API -->
        <div v-if="props.errors?.api_error"
            class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
            <div class="flex items-center gap-2">
                <AlertTriangle class="w-5 h-5" />
                <span class="font-medium">{{ props.errors.api_error }}</span>
            </div>
        </div>

        <div class="space-y-6 mx-auto">
            <!-- Alerta -->
            <div class="bg-blue-50 rounded-xl border border-blue-200 p-4">
                <div class="flex items-start gap-3">
                    <Info class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" />
                    <div>
                        <h3 class="font-semibold text-blue-900 mb-1">Como funciona?</h3>
                        <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
                            <li>Preencha os dados da credencial fornecida pelo Clube</li>
                            <li>Ao salvar, o sistema <strong>autentica automaticamente</strong> na API</li>
                            <li>O token de acesso será armazenado com data de expiração</li>
                            <li>O token é válido por <strong>1 ano</strong> (31536000 segundos)</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                        <div
                            class="flex items-center gap-2 mb-6 text-gray-900 font-semibold border-b border-gray-100 pb-4">
                            <Shield class="w-5 h-5 text-cyan-600" />
                            <h2>Dados da Credencial OAuth</h2>
                        </div>

                        <div class="space-y-6">
                            <!-- Título -->
                            <div>
                                <Label for="title" class="text-gray-700 pb-2 font-medium flex items-center gap-2">
                                    <Tag class="w-4 h-4" />
                                    Título/Identificação
                                    <span class="text-gray-400 text-xs font-normal">(opcional)</span>
                                </Label>
                                <input id="title" v-model="form.title" type="text"
                                    placeholder="Ex: Produção, Staging, Cliente X..."
                                    :class="form.errors.title ? inputErrorClass : inputClass"
                                    :disabled="form.processing" />
                                <p v-if="form.errors.title" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.title }}
                                </p>
                            </div>

                            <!-- Grant Type -->
                            <div>
                                <Label for="grant_type" class="text-gray-700 pb-2 font-medium">
                                    Grant Type <span class="text-red-500">*</span>
                                </Label>
                                <select id="grant_type" v-model="form.grant_type"
                                    :class="form.errors.grant_type ? inputErrorClass : inputClass"
                                    :disabled="form.processing || isEdit">
                                    <option value="client_credentials">Client Credentials</option>
                                    <option value="password">Password</option>
                                    <option value="authorization_code">Authorization Code</option>
                                    <option value="refresh_token">Refresh Token</option>
                                </select>
                                <p v-if="form.errors.grant_type" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.grant_type }}
                                </p>
                            </div>

                            <!-- Client ID -->
                            <div>
                                <Label for="client_id" class="text-gray-700 pb-2 font-medium">
                                    Client ID <span class="text-red-500">*</span>
                                </Label>
                                <input id="client_id" v-model="form.client_id" type="text"
                                    placeholder="a1424432-7484-4cd6-bfae-24ce9e9496dc"
                                    :class="form.errors.client_id ? inputErrorClass : inputClass"
                                    :disabled="form.processing || isEdit" />
                                <p v-if="form.errors.client_id" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.client_id }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    UUID fornecido pelo Clube Parcerias
                                </p>
                            </div>

                            <!-- Client Secret -->
                            <div>
                                <Label for="client_secret"
                                    class="text-gray-700 pb-2 font-medium flex items-center gap-2">
                                    <Lock class="w-4 h-4" />
                                    Client Secret <span class="text-red-500">*</span>
                                </Label>
                                <div class="relative">
                                    <input id="client_secret" v-model="form.client_secret"
                                        :type="showSecret ? 'text' : 'password'"
                                        placeholder="xXQATwoCpqlK2w9BUo1XYHbSayJ3NERuE1ETqBxW"
                                        :class="[form.errors.client_secret ? inputErrorClass : inputClass, 'pr-10']"
                                        :disabled="form.processing" />
                                    <button type="button" @click="showSecret = !showSecret"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                                        <Eye v-if="!showSecret" class="w-5 h-5" />
                                        <EyeOff v-else class="w-5 h-5" />
                                    </button>
                                </div>
                                <p v-if="form.errors.client_secret" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.client_secret }}
                                </p>
                            </div>

                            <!-- Scope -->
                            <div>
                                <Label for="scope" class="text-gray-700 pb-2 font-medium">
                                    Scope
                                </Label>
                                <input id="scope" v-model="form.scope" type="text" placeholder="*"
                                    :class="form.errors.scope ? inputErrorClass : inputClass"
                                    :disabled="form.processing" />
                                <p class="mt-1 text-xs text-gray-500">
                                    Use <code class="bg-gray-100 px-1 rounded">*</code> para todas as permissões
                                </p>
                                <p v-if="form.errors.scope" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.scope }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <!-- Preview -->
                    <div class="bg-gray-50 rounded-xl border border-gray-200 p-6">
                        <h3 class="font-semibold text-gray-700 mb-3">Resumo</h3>
                        <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm space-y-3">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-lg bg-cyan-100 flex items-center justify-center flex-shrink-0">
                                    <Shield class="w-5 h-5 text-cyan-600" />
                                </div>
                                <div class="min-w-0">
                                    <h4 class="font-semibold text-gray-900 truncate">
                                        {{ form.title || 'Sem título' }}
                                    </h4>
                                    <p class="text-sm text-gray-500 font-mono text-xs truncate">
                                        {{ form.client_id || 'Client ID não informado' }}
                                    </p>
                                </div>
                            </div>

                            <div class="pt-3 border-t border-gray-100 space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Grant Type:</span>
                                    <code class="bg-gray-100 px-2 py-0.5 rounded text-xs">{{ form.grant_type }}</code>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Scope:</span>
                                    <code class="bg-gray-100 px-2 py-0.5 rounded text-xs">{{ form.scope || '*' }}</code>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Secret:</span>
                                    <span class="text-gray-700">
                                        {{ form.client_secret ? '••••••••' : 'Não informado' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Info Box -->
                    <div class="bg-amber-50 rounded-xl border border-amber-200 p-6">
                        <h3 class="font-semibold text-amber-900 mb-2 flex items-center gap-2">
                            <AlertTriangle class="w-5 h-5" />
                            Atenção
                        </h3>
                        <p class="text-sm text-amber-800">
                            O <strong>Client Secret</strong> é uma informação sensível.
                            Ele será criptografado no banco de dados e não será exibido novamente após salvar.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </CentralAdminLayout>
</template>
