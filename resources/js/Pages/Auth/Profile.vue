<script setup>
import { computed, ref } from "vue";
import { Head, useForm, router, usePage } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import { Label } from "@/Components/ui/label";
import CentralAdminLayout from "@/Layouts/CentralAdminLayout.vue";
import TenantAdminLayout from "@/Layouts/TenantAdminLayout.vue";
import { showToast } from '@/Utils/toast';
import Breadcrumb from "@/Components/Breadcrumb.vue";
import InputError from "@/Components/InputError.vue";
import ConfirmDeleteModal from '@/Components/ConfirmDeleteModal.vue';
import { useAbaAtiva } from '@/Composables/Pagina/useAbaAtiva';
import {
    ArrowLeft,
    Save,
    UserCircle,
    Home,
    Mail,
    Shield,
    KeyRound,
    AlertTriangle,
    CheckCircle2,
    Trash2,
    Eye,
    EyeOff,
    Calendar,
    Key,
    Lock,
    Camera,
    Loader2
} from "lucide-vue-next";
const page = usePage();
const auth = computed(() => page.props.authUser);
const user = computed(() => page.props.authUser?.user);
const can = computed(() => page.props.authUser?.can);
// Vem do servidor (tenancy inicializada): o domínio central em produção não é
// localhost, então adivinhar pelo endereço abria o layout do parceiro no central.
const props = defineProps({
    isTenant: { type: Boolean, default: false },
});
const isTenant = computed(() => props.isTenant);

const layout = computed(() => isTenant.value ? TenantAdminLayout : CentralAdminLayout);

const homeRoute = computed(() => isTenant.value ? 'patients.index' : 'dashboard');

const breadcrumbs = computed(() => [
    { label: 'Início', href: route(homeRoute.value), icon: Home },
    { label: 'Meu Perfil', href: null },
]);

const goHome = () => {
    router.visit(route(homeRoute.value));
};
// Abas do perfil (persistidas em ?aba= para sobreviver ao redirect após salvar).
const tabs = [
    { key: 'dados', label: 'Dados pessoais', icon: UserCircle },
    { key: 'seguranca', label: 'Segurança', icon: KeyRound },
    { key: 'permissoes', label: 'Permissões', icon: Key },
];
const { activeTab } = useAbaAtiva('perfil:aba', tabs.map((tab) => tab.key), 'dados');

const perfilForm = useForm({
    name: user.value?.name || '',
    email: user.value?.email || '',
});
const updateperfil = () => {
    perfilForm.patch(route('perfil.update'), {
        preserveScroll: true,
        onSuccess: () => {
            showToast('Perfil atualizado com sucesso!', 'success');
        },
        onError: () => {
            showToast('Erro ao atualizar perfil.', 'error');
        }
    });
};
// Foto de perfil: envia assim que o arquivo é escolhido.
const FOTO_TIPOS = ['image/jpeg', 'image/png', 'image/webp'];
const FOTO_MAX = 2 * 1024 * 1024;
const fotoInput = ref(null);
const fotoPreview = ref(null);
const fotoForm = useForm({ foto: null });
const removendoFoto = ref(false);
const fotoAtual = computed(() => fotoPreview.value || user.value?.avatar || null);

const escolherFoto = () => fotoInput.value?.click();

const limparPreview = () => {
    if (fotoPreview.value) URL.revokeObjectURL(fotoPreview.value);
    fotoPreview.value = null;
};

const enviarFoto = (event) => {
    const arquivo = event.target.files?.[0];
    event.target.value = '';
    if (!arquivo) return;

    if (!FOTO_TIPOS.includes(arquivo.type)) {
        showToast('Use uma imagem JPG, PNG ou WEBP.', 'warning');
        return;
    }
    if (arquivo.size > FOTO_MAX) {
        showToast('A foto deve ter no máximo 2 MB.', 'warning');
        return;
    }

    limparPreview();
    fotoPreview.value = URL.createObjectURL(arquivo);
    fotoForm.foto = arquivo;
    fotoForm.post(route('perfil.foto.update'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => showToast('Foto atualizada com sucesso!', 'success'),
        onError: (errors) => showToast(errors.foto || 'Erro ao enviar a foto.', 'error'),
        onFinish: () => {
            limparPreview();
            fotoForm.reset();
        },
    });
};

const removerFoto = () => {
    removendoFoto.value = true;
    router.delete(route('perfil.foto.destroy'), {
        preserveScroll: true,
        onSuccess: () => showToast('Foto removida.', 'success'),
        onError: () => showToast('Erro ao remover a foto.', 'error'),
        onFinish: () => { removendoFoto.value = false; },
    });
};

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});
const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);
const updatePassword = () => {
    passwordForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            showToast('Senha atualizada com sucesso!', 'success');
            passwordForm.reset();
        },
        onError: () => {
            showToast('Erro ao atualizar senha.', 'error');
        }
    });
};
const deleteModal = ref({
    show: false,
    isProcessing: false
});
const deleteForm = useForm({
    password: '',
});
const openDeleteModal = () => {
    deleteModal.value.show = true;
};
const closeDeleteModal = () => {
    deleteModal.value.show = false;
    deleteForm.reset();
};
const confirmDelete = () => {
    deleteModal.value.isProcessing = true;
    deleteForm.delete(route('perfil.destroy'), {
        preserveScroll: true,
        onSuccess: () => { },
        onError: () => {
            showToast('Senha incorreta. Tente novamente.', 'error');
            deleteModal.value.isProcessing = false;
        },
        onFinish: () => {
            deleteModal.value.isProcessing = false;
        }
    });
};
const getRoleColor = (role) => {
    const colors = {
        'Admin': 'bg-purple-100 text-purple-700 border-purple-200',
        'Manager': 'bg-blue-100 text-blue-700 border-blue-200',
        'Editor': 'bg-green-100 text-green-700 border-green-200',
        'User': 'bg-gray-100 text-gray-700 border-gray-200',
    };
    return colors[role] || 'bg-gray-100 text-gray-700 border-gray-200';
};
const getInitials = (name) => {
    if (!name) return 'U';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};
const goBack = () => {
    router.visit(route(homeRoute.value));
};
</script>
<template>

    <Head title="Meu Perfil" />
    <component :is="layout">
        <div class=" mx-auto space-y-6">
            <!-- ════════════════════════════════════════ -->
            <!-- CARD: INFORMAÇÕES DO USUÁRIO -->
            <!-- ════════════════════════════════════════ -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-6 sm:p-8">
                    <div class="flex flex-col sm:flex-row items-center gap-6">
                        <!-- Avatar Grande -->
                        <div class="flex flex-col items-center gap-2">
                            <div class="relative">
                                <button type="button" @click="escolherFoto" :disabled="fotoForm.processing"
                                    :aria-label="fotoAtual ? 'Trocar foto de perfil' : 'Adicionar foto de perfil'"
                                    class="group relative block w-24 h-24 rounded-full overflow-hidden shadow-lg focus:outline-none focus-visible:ring-4 focus-visible:ring-cyan-300">
                                    <img v-if="fotoAtual" :src="fotoAtual" alt="Foto de perfil"
                                        class="w-full h-full object-cover" />
                                    <span v-else
                                        class="w-full h-full bg-gradient-to-br from-cyan-400 to-blue-500 flex items-center justify-center text-white text-3xl font-bold">
                                        {{ getInitials(user?.name) }}
                                    </span>
                                    <span
                                        class="absolute inset-0 flex items-center justify-center bg-black/45 text-white transition-opacity"
                                        :class="fotoForm.processing ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'">
                                        <Loader2 v-if="fotoForm.processing" class="w-6 h-6 animate-spin" />
                                        <Camera v-else class="w-6 h-6" />
                                    </span>
                                </button>
                                <button type="button" @click="escolherFoto" :disabled="fotoForm.processing"
                                    title="Alterar foto"
                                    class="absolute -bottom-1 -right-1 w-8 h-8 bg-cyan-500 hover:bg-cyan-600 rounded-full border-4 border-white flex items-center justify-center transition-colors">
                                    <Camera class="w-3.5 h-3.5 text-white" />
                                </button>
                                <input ref="fotoInput" type="file" accept="image/jpeg,image/png,image/webp" class="hidden"
                                    @change="enviarFoto" />
                            </div>
                            <button v-if="user?.avatar && !fotoForm.processing" type="button" @click="removerFoto"
                                :disabled="removendoFoto"
                                class="text-xs font-medium text-gray-500 hover:text-red-600 transition-colors disabled:opacity-50">
                                {{ removendoFoto ? 'Removendo...' : 'Remover foto' }}
                            </button>
                            <span v-else-if="!user?.avatar" class="text-xs text-gray-400">JPG, PNG ou WEBP · até 2 MB</span>
                        </div>
                        <!-- Info -->
                        <div class="text-center sm:text-left flex-1">
                            <h2 class="text-2xl font-bold text-gray-900">{{ user?.name }}</h2>
                            <div class="flex flex-col sm:flex-row items-center gap-2 mt-1 text-sm text-gray-500">
                                <Mail class="w-4 h-4" />
                                <span>{{ user?.email }}</span>
                                <span v-if="user?.email_verified_at" class="text-green-600 flex items-center gap-1">
                                    <CheckCircle2 class="w-3 h-3" />
                                    Verificado
                                </span>
                                <span v-else
                                    class="text-amber-600 flex items-center gap-1 text-xs bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">
                                    <Lock class="w-3 h-3" />
                                    Não verificado
                                </span>
                            </div>
                            <div class="flex flex-wrap justify-center sm:justify-start gap-2 mt-3">
                                <span v-for="role in user?.roles" :key="role"
                                    class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium border"
                                    :class="getRoleColor(role)">
                                    <Shield class="w-3 h-3" />
                                    {{ role }}
                                </span>
                            </div>
                            <p
                                class="text-xs text-gray-400 mt-2 flex items-center justify-center sm:justify-start gap-1">
                                <Calendar class="w-3 h-3" />
                                Membro desde {{ user?.created_at }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Abas -->
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="border-b border-gray-100 bg-gray-50/70 px-4 md:px-6 pt-4">
                    <div class="overflow-x-auto">
                        <nav class="flex min-w-max gap-2 pb-3" aria-label="Seções do perfil">
                            <button v-for="tab in tabs" :key="tab.key" type="button"
                                :aria-current="activeTab === tab.key ? 'page' : undefined"
                                @click="activeTab = tab.key"
                                class="group flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-medium transition-all duration-200 whitespace-nowrap border"
                                :class="activeTab === tab.key
                                    ? 'bg-white text-cyan-600 border-cyan-200 shadow-sm'
                                    : 'bg-transparent text-gray-600 border-transparent hover:bg-white hover:border-gray-200 hover:text-gray-900'">
                                <component :is="tab.icon" class="w-4 h-4 shrink-0"
                                    :class="activeTab === tab.key ? 'text-cyan-500' : 'text-gray-400 group-hover:text-gray-600'" />
                                {{ tab.label }}
                                <span v-if="tab.key === 'dados' && perfilForm.isDirty" class="h-2 w-2 rounded-full bg-amber-500"
                                    title="Alterações não salvas" />
                            </button>
                        </nav>
                    </div>
                </div>

                <div class="p-4 md:p-6">
                    <!-- Dados pessoais -->
                    <div v-if="activeTab === 'dados'" class="space-y-6">
                <!-- Editar Perfil -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                    <div
                        class="flex items-center gap-2 mb-6 text-gray-900 font-semibold border-b border-gray-100 pb-4">
                        <UserCircle class="w-5 h-5 text-cyan-600" />
                        <h2>Dados Pessoais</h2>
                    </div>
                    <form @submit.prevent="updateperfil" class="space-y-5">
                        <!-- Nome -->
                        <div>
                            <Label for="name" class="text-gray-700 font-medium">
                                Nome Completo
                            </Label>
                            <input id="name" v-model="perfilForm.name" type="text"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500"
                                :class="{ 'border-red-500': perfilForm.errors.name }" />
                            <InputError v-if="perfilForm.errors.name" :message="perfilForm.errors.name"
                                class="mt-1" />
                        </div>
                        <!-- Email -->
                        <div>
                            <Label for="email" class="text-gray-700 font-medium">
                                E-mail
                            </Label>
                            <input id="email" v-model="perfilForm.email" type="email"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500"
                                :class="{ 'border-red-500': perfilForm.errors.email }" />
                            <InputError v-if="perfilForm.errors.email" :message="perfilForm.errors.email"
                                class="mt-1" />
                        </div>
                        <!-- Botão Salvar -->
                        <div class="pt-2">
                            <Button type="submit" :disabled="perfilForm.processing || (!perfilForm.isDirty)"
                                class="w-full sm:w-auto gap-2 bg-cyan-500 hover:bg-cyan-600">
                                <Save class="w-4 h-4" />
                                <span v-if="perfilForm.processing">Salvando...</span>
                                <span v-else>Salvar Alterações</span>
                            </Button>
                        </div>
                    </form>
                </div>
                    </div>

                    <!-- Segurança -->
                    <div v-if="activeTab === 'seguranca'" class="space-y-6">
                <!-- Alterar Senha -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                    <div
                        class="flex items-center gap-2 mb-6 text-gray-900 font-semibold border-b border-gray-100 pb-4">
                        <KeyRound class="w-5 h-5 text-cyan-600" />
                        <h2>Segurança</h2>
                    </div>
                    <form @submit.prevent="updatePassword" class="space-y-5">
                        <!-- Senha Atual -->
                        <div>
                            <Label for="current_password" class="text-gray-700 font-medium">
                                Senha Atual
                            </Label>
                            <div class="relative mt-1">
                                <input id="current_password" v-model="passwordForm.current_password"
                                    :type="showCurrentPassword ? 'text' : 'password'"
                                    class="block w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500"
                                    :class="{ 'border-red-500': passwordForm.errors.current_password }" />
                                <button type="button" @click="showCurrentPassword = !showCurrentPassword"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                    <Eye v-if="!showCurrentPassword" class="w-4 h-4" />
                                    <EyeOff v-else class="w-4 h-4" />
                                </button>
                            </div>
                            <InputError v-if="passwordForm.errors.current_password"
                                :message="passwordForm.errors.current_password" class="mt-1" />
                        </div>
                        <!-- Nova Senha -->
                        <div>
                            <Label for="password" class="text-gray-700 font-medium">
                                Nova Senha
                            </Label>
                            <div class="relative mt-1">
                                <input id="password" v-model="passwordForm.password"
                                    :type="showNewPassword ? 'text' : 'password'"
                                    class="block w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500"
                                    :class="{ 'border-red-500': passwordForm.errors.password }" />
                                <button type="button" @click="showNewPassword = !showNewPassword"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                    <Eye v-if="!showNewPassword" class="w-4 h-4" />
                                    <EyeOff v-else class="w-4 h-4" />
                                </button>
                            </div>
                            <InputError v-if="passwordForm.errors.password" :message="passwordForm.errors.password"
                                class="mt-1" />
                            <p class="text-xs text-gray-500 mt-1">Mínimo 8 caracteres</p>
                        </div>
                        <!-- Confirmar Senha -->
                        <div>
                            <Label for="password_confirmation" class="text-gray-700 font-medium">
                                Confirmar Nova Senha
                            </Label>
                            <div class="relative mt-1">
                                <input id="password_confirmation" v-model="passwordForm.password_confirmation"
                                    :type="showConfirmPassword ? 'text' : 'password'"
                                    class="block w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500"
                                    :class="{ 'border-red-500': passwordForm.errors.password_confirmation }" />
                                <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                    <Eye v-if="!showConfirmPassword" class="w-4 h-4" />
                                    <EyeOff v-else class="w-4 h-4" />
                                </button>
                            </div>
                            <InputError v-if="passwordForm.errors.password_confirmation"
                                :message="passwordForm.errors.password_confirmation" class="mt-1" />
                        </div>
                        <!-- Botão -->
                        <div class="pt-2">
                            <Button type="submit" :disabled="passwordForm.processing || !passwordForm.password"
                                class="w-full sm:w-auto gap-2 bg-cyan-500 hover:bg-cyan-600">
                                <KeyRound class="w-4 h-4" />
                                <span v-if="passwordForm.processing">Atualizando...</span>
                                <span v-else>Alterar Senha</span>
                            </Button>
                        </div>
                    </form>
                </div>
                <!-- Zona de Perigo -->
                <div class="bg-red-50 rounded-xl border border-red-200 p-6">
                    <div class="flex items-center gap-2 mb-4 text-red-900 font-semibold">
                        <AlertTriangle class="w-5 h-5" />
                        <h2>Zona de Perigo</h2>
                    </div>
                    <p class="text-sm text-red-700 mb-4">
                        Ao excluir sua conta, todos os seus dados serão permanentemente removidos. Esta ação não
                        pode ser
                        desfeita.
                    </p>
                    <Button variant="outline" @click="openDeleteModal"
                        class="gap-2 border-red-300 text-red-600 hover:bg-red-100 hover:text-red-700">
                        <Trash2 class="w-4 h-4" />
                        Excluir Minha Conta
                    </Button>
                </div>
                    </div>

                    <!-- Permissões -->
                    <div v-if="activeTab === 'permissoes'" class="space-y-6">
                <!-- Minhas Permissões (com CAN) -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                    <div
                        class="flex items-center gap-2 mb-4 text-gray-900 font-semibold border-b border-gray-100 pb-4">
                        <Key class="w-5 h-5 text-cyan-600" />
                        <h2>Minhas Permissões</h2>
                    </div>
                    <!-- Permissões por Módulo -->
                    <div class="space-y-4">
                        <!-- Users -->
                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Usuários
                            </h4>
                            <div class="flex flex-wrap gap-2">
                                <span v-if="can?.users?.view"
                                    class="px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-700 border border-green-200">Ver</span>
                                <span v-if="can?.users?.create"
                                    class="px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-700 border border-green-200">Criar</span>
                                <span v-if="can?.users?.edit"
                                    class="px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-700 border border-green-200">Editar</span>
                                <span v-if="can?.users?.delete"
                                    class="px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-700 border border-green-200">Excluir</span>
                                <span v-if="can?.users?.manage"
                                    class="px-2 py-1 rounded-md text-xs font-medium bg-purple-100 text-purple-700 border border-purple-200">Gerenciar</span>
                            </div>
                        </div>
                        <!-- Forms -->
                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                                Formulários</h4>
                            <div class="flex flex-wrap gap-2">
                                <span v-if="can?.forms?.view"
                                    class="px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700 border border-blue-200">Ver</span>
                                <span v-if="can?.forms?.create"
                                    class="px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700 border border-blue-200">Criar</span>
                                <span v-if="can?.forms?.edit"
                                    class="px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700 border border-blue-200">Editar</span>
                                <span v-if="can?.forms?.delete"
                                    class="px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700 border border-blue-200">Excluir</span>
                                <span v-if="can?.forms?.manage_all"
                                    class="px-2 py-1 rounded-md text-xs font-medium bg-purple-100 text-purple-700 border border-purple-200">Gerenciar
                                    Todos</span>
                            </div>
                        </div>
                        <!-- Paginas -->
                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Páginas
                            </h4>
                            <div class="flex flex-wrap gap-2">
                                <span v-if="can?.paginas?.view"
                                    class="px-2 py-1 rounded-md text-xs font-medium bg-cyan-100 text-cyan-700 border border-cyan-200">Ver</span>
                                <span v-if="can?.paginas?.create"
                                    class="px-2 py-1 rounded-md text-xs font-medium bg-cyan-100 text-cyan-700 border border-cyan-200">Criar</span>
                                <span v-if="can?.paginas?.edit"
                                    class="px-2 py-1 rounded-md text-xs font-medium bg-cyan-100 text-cyan-700 border border-cyan-200">Editar</span>
                                <span v-if="can?.paginas?.delete"
                                    class="px-2 py-1 rounded-md text-xs font-medium bg-cyan-100 text-cyan-700 border border-cyan-200">Excluir</span>
                                <span v-if="can?.paginas?.manage"
                                    class="px-2 py-1 rounded-md text-xs font-medium bg-purple-100 text-purple-700 border border-purple-200">Gerenciar</span>
                            </div>
                        </div>
                        <!-- Leis -->
                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Leis</h4>
                            <div class="flex flex-wrap gap-2">
                                <span v-if="can?.leis?.view"
                                    class="px-2 py-1 rounded-md text-xs font-medium bg-orange-100 text-orange-700 border border-orange-200">Ver</span>
                                <span v-if="can?.leis?.create"
                                    class="px-2 py-1 rounded-md text-xs font-medium bg-orange-100 text-orange-700 border border-orange-200">Criar</span>
                                <span v-if="can?.leis?.edit"
                                    class="px-2 py-1 rounded-md text-xs font-medium bg-orange-100 text-orange-700 border border-orange-200">Editar</span>
                                <span v-if="can?.leis?.delete"
                                    class="px-2 py-1 rounded-md text-xs font-medium bg-orange-100 text-orange-700 border border-orange-200">Excluir</span>
                                <span v-if="!can?.leis?.view"
                                    class="px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-500 border border-gray-200">Sem
                                    acesso</span>
                            </div>
                        </div>
                    </div>
                    <!-- Resumo -->
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500">Modo Administrador:</span>
                            <span
                                :class="['text-xs font-medium', can?.manage ? 'text-green-600' : 'text-gray-400']">
                                {{ can?.manage ? 'Ativo' : 'Inativo' }}
                            </span>
                        </div>
                    </div>
                </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal de Confirmação de Exclusão -->
        <ConfirmDeleteModal :show="deleteModal.show" item-name="sua conta" title="Excluir Conta"
            message="Tem certeza que deseja excluir sua conta permanentemente?"
            warning-message="Todos os seus dados serão removidos. Esta ação não pode ser desfeita."
            confirm-text="Sim, Excluir Conta" cancel-text="Cancelar" :is-processing="deleteModal.isProcessing"
            variant="danger" @close="closeDeleteModal" @confirm="confirmDelete">
            <template #extra>
                <div class="mt-4">
                    <Label for="delete_password" class="text-gray-700 font-medium">
                        Digite sua senha para confirmar
                    </Label>
                    <input id="delete_password" v-model="deleteForm.password" type="password"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                        :class="{ 'border-red-500': deleteForm.errors.password }" placeholder="Sua senha atual" />
                    <InputError v-if="deleteForm.errors.password" :message="deleteForm.errors.password" class="mt-1" />
                </div>
            </template>
        </ConfirmDeleteModal>
    </component>
</template>
