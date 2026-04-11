<script setup>
import CentralAdminLayout from '@/Layouts/CentralAdminLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import { showToast } from '@/Utils/toast';
import {
    ArrowLeft,
    Save,
    Shield,
    Lock,
    Eye,
    EyeOff,
    FileText,
    Key,
    RefreshCw,
    AlertTriangle,
    CheckCircle2,
    XCircle,
    Clock
} from 'lucide-vue-next';
import { ref, computed } from 'vue';

const props = defineProps({
    credencial: {
        type: Object,
        required: true
    },
    errors: Object
});

const breadcrumbs = computed(() => [
    { label: 'Início', href: route('dashboard'), icon: null },
    { label: 'Configurações', href: null, icon: null },
    { label: 'Credenciais Clubles', href: route('configuracoes.credencias_cluble.index'), icon: null },
    { label: 'Editar Credencial', href: null, icon: null },
]);

const inputClass = "w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-500";
const inputErrorClass = "w-full px-3 py-2 border border-red-500 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500";
const inputDisabledClass = "w-full px-3 py-2 border border-gray-200 bg-gray-100 text-gray-500 rounded-md cursor-not-allowed";

const form = useForm({
    title: props.credencial.title || '',
    credencial: props.credencial?.access_token ?? '',
    client_secret: '', // Vazio por segurança, só preenche se quiser alterar
    scope: props.credencial.scope || '*',
});

const showSecret = ref(false);

// Status do token
const getTokenStatus = computed(() => {
    if (!props.credencial.token_expires_at) {
        return { label: 'Pendente', color: 'gray', icon: Clock };
    }

    const expiresAt = new Date(props.credencial.token_expires_at);
    const now = new Date();

    if (expiresAt < now) {
        return { label: 'Expirado', color: 'red', icon: XCircle };
    }

    const sevenDays = 7 * 24 * 60 * 60 * 1000;
    if (expiresAt - now < sevenDays) {
        return { label: 'Expira em breve', color: 'yellow', icon: AlertTriangle };
    }

    return { label: 'Válido', color: 'green', icon: CheckCircle2 };
});

const submit = () => {
    form.put(route('configuracoes.credencias_cluble.update', props.credencial.id), {
        preserveScroll: true,
        onSuccess: () => {
            showToast('Credencial atualizada com sucesso!', 'success');
            form.reset('client_secret');
        },
        onError: (errors) => {
            const message = errors.api_error || errors.title || 'Erro ao atualizar credencial';
            showToast(message, 'error');
        }
    });
};

const goBack = () => {
    router.visit(route('configuracoes.credencias_cluble.index'));
};

const refreshToken = () => {
    router.post(route('configuracoes.credencias_cluble.refresh', props.credencial.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            showToast('Token renovado com sucesso!', 'success');
        },
        onError: () => {
            showToast('Erro ao renovar token', 'error');
        }
    });
};

const formatDateTime = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString('pt-BR');
};
</script>

<template>

    <Head title="Editar Credencial" />

    <CentralAdminLayout>
        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-2 mb-6">
            <div class="space-y-1">
                <Breadcrumb :items="breadcrumbs" />
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">
                    Editar Credencial Cluble
                </h1>
                <p class="text-sm text-gray-500">
                    Atualize as informações da credencial de acesso à API
                </p>
            </div>

            <div class="flex gap-2">
                <Button variant="outline" @click="goBack" class="gap-2">
                    <ArrowLeft class="w-4 h-4" />
                    Voltar
                </Button>

                <Button variant="primary" @click="submit" :disabled="form.processing" class="gap-2">
                    <Save class="w-4 h-4" />
                    <span v-if="form.processing">Salvando...</span>
                    <span v-else>Atualizar</span>
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
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <!-- Tokens (readonly) -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                        <div
                            class="flex items-center gap-2 mb-6 text-gray-900 font-semibold border-b border-gray-100 pb-4">
                            <Key class="w-5 h-5 text-cyan-600" />
                            <h2>Tokens de Acesso</h2>
                        </div>

                        <div class="space-y-4">
                            <!-- Access Token -->
                            <div>
                                <Label class="text-gray-700 pb-2 font-medium text-sm">Access Token</Label>
                                <div
                                    class="bg-gray-50 border border-gray-200 rounded-md p-3 font-mono text-xs text-gray-600 break-all">
                                    <div v-if="credencial">
                                        ••••••••••••••••••••••••••••••••••••••••••••••••
                                    </div>
                                    <div v-else>
                                        Não
                                        disponível
                                    </div>

                                </div>
                            </div>

                            <!-- Refresh Token -->
                            <div>
                                <Label class="text-gray-700 pb-2 font-medium text-sm">Refresh Token</Label>
                                <div
                                    class="bg-gray-50 border border-gray-200 rounded-md p-3 font-mono text-xs text-gray-600">
                                    {{ credencial.refresh_token ? '••••••••••••' : 'Não disponível' }}
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <Label class="text-gray-700 pb-2 font-medium text-sm">Token Type</Label>
                                    <div class="text-sm text-gray-900">{{ credencial.token_type || '-' }}</div>
                                </div>
                                <div>
                                    <Label class="text-gray-700 pb-2 font-medium text-sm">Expires In</Label>
                                    <div class="text-sm text-gray-900">{{ credencial.expires_in ? credencial.expires_in
                                        + ' segundos' : '-' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Dados da Credencial -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                        <div
                            class="flex items-center gap-2 mb-6 text-gray-900 font-semibold border-b border-gray-100 pb-4">
                            <Shield class="w-5 h-5 text-cyan-600" />
                            <h2>Dados da Credencial OAuth</h2>
                        </div>

                        <div class="space-y-6">
                            <!-- Título -->
                            <div>
                                <Label for="title" class="text-gray-700 pb-2 font-medium">
                                    Título/Identificação
                                    <span class="text-gray-400 text-xs font-normal ml-1">(opcional)</span>
                                </Label>
                                <input id="title" v-model="form.title" type="text"
                                    placeholder="Ex: Produção, Staging, Cliente X..."
                                    :class="form.errors.title ? inputErrorClass : inputClass"
                                    :disabled="form.processing" />
                                <p v-if="form.errors.title" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.title }}
                                </p>
                            </div>

                            <!-- Client ID (readonly) -->
                            <div>
                                <Label for="client_id" class="text-gray-700 pb-2 font-medium">
                                    Client ID
                                </Label>
                                <input id="client_id" :value="credencial.client_id" type="text"
                                    :class="inputDisabledClass" disabled />
                                <p class="mt-1 text-xs text-gray-500">
                                    O Client ID não pode ser alterado
                                </p>
                            </div>

                            <!-- Grant Type (readonly) -->
                            <div>
                                <Label for="grant_type" class="text-gray-700 pb-2 font-medium">
                                    Grant Type
                                </Label>
                                <input id="grant_type" :value="credencial.grant_type" type="text"
                                    :class="inputDisabledClass" disabled />
                            </div>

                            <!-- Client Secret -->
                            <div>
                                <Label for="client_secret"
                                    class="text-gray-700 pb-2 font-medium flex items-center gap-2">
                                    <Lock class="w-4 h-4" />
                                    Novo Client Secret
                                    <span class="text-gray-400 text-xs font-normal ml-1">(deixe em branco para manter o
                                        atual)</span>
                                </Label>
                                <div class="relative">
                                    <input id="client_secret" v-model="form.client_secret"
                                        :type="showSecret ? 'text' : 'password'"
                                        placeholder="Preencha apenas se deseja alterar..."
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
                    <!-- Status do Token -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                        <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <Key class="w-4 h-4" />
                            Status do Token
                        </h3>

                        <div class="flex items-center gap-3 mb-4">
                            <component :is="getTokenStatus.icon" class="w-8 h-8" :class="{
                                'text-green-500': getTokenStatus.color === 'green',
                                'text-red-500': getTokenStatus.color === 'red',
                                'text-yellow-500': getTokenStatus.color === 'yellow',
                                'text-gray-400': getTokenStatus.color === 'gray'
                            }" />
                            <div>
                                <p class="text-sm text-gray-500">Situação atual</p>
                                <p class="font-semibold" :class="{
                                    'text-green-700': getTokenStatus.color === 'green',
                                    'text-red-700': getTokenStatus.color === 'red',
                                    'text-yellow-700': getTokenStatus.color === 'yellow',
                                    'text-gray-500': getTokenStatus.color === 'gray'
                                }">
                                    {{ getTokenStatus.label }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-3 text-sm border-t border-gray-100 pt-4">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Criado em:</span>
                                <span class="text-gray-900">{{ formatDateTime(credencial.created_at) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Expira em:</span>
                                <span class="text-gray-900">{{ formatDateTime(credencial.token_expires_at) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Última atualização:</span>
                                <span class="text-gray-900">{{ formatDateTime(credencial.updated_at) }}</span>
                            </div>
                        </div>

                        <Button variant="outline" class="w-full mt-4 gap-2" @click="refreshToken"
                            :disabled="form.processing">
                            <RefreshCw class="w-4 h-4" />
                            Renovar Token Agora
                        </Button>
                    </div>

                    <!-- Informações do Registro -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                        <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <FileText class="w-4 h-4" />
                            Informações do Registro
                        </h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-500">ID:</span>
                                <span class="font-mono font-medium text-gray-900">#{{ credencial.id }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-500">Criado em:</span>
                                <span class="text-gray-900">{{ formatDateTime(credencial.created_at) }}</span>
                            </div>
                            <div class="flex justify-between py-2">
                                <span class="text-gray-500">Atualizado em:</span>
                                <span class="text-gray-900">{{ formatDateTime(credencial.updated_at) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Aviso -->
                    <div class="bg-amber-50 rounded-xl border border-amber-200 p-6">
                        <h3 class="font-semibold text-amber-900 mb-2 flex items-center gap-2">
                            <AlertTriangle class="w-5 h-5" />
                            Atenção
                        </h3>
                        <p class="text-sm text-amber-800">
                            Alterar o <strong>Client Secret</strong> ou <strong>Scope</strong> pode exigir uma nova
                            autenticação na API.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </CentralAdminLayout>
</template>
