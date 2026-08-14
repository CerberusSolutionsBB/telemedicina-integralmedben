<script setup>
import { Link, useForm, usePage } from "@inertiajs/vue3";
import {
    Users,
    UserCircle,
    LogOut,
    ChevronDown,
    ClipboardList,
    Bell,
    Shield,
    LayoutDashboard,
    Settings,
} from "lucide-vue-next";
import { computed, ref } from "vue";

const props = defineProps({
    tenantName: {
        type: String,
        default: "",
    },
    tenantPhoto: {
        type: String,
        default: null,
    },
});

const page = usePage();

const authUser = computed(() => page.props.auth?.user);
const tenantPublic = computed(() => page.props.tenant_public);

const logoutForm = useForm({});

const showUserMenu = ref(false);

const logout = () => {
    logoutForm.post(route("tenant.logout"));
};

const navLinks = [
    { label: "Pacientes", routeName: "patients.index", icon: Users },
    { label: "Usuários", routeName: "users.index", icon: UserCircle },
    {
        label: "Meus Formulários",
        routeName: "meus-formularios.index",
        icon: ClipboardList,
    },
    {
        label: "Configurações",
        routeName: "configuracao.index",
        icon: Settings,
    },
];

const userInitial = computed(() => {
    return authUser.value?.name?.charAt(0)?.toUpperCase() || "U";
});

const tenantInitial = computed(() => {
    return (
        tenantPublic.value?.detail?.sigla ||
        tenantPublic.value?.slug?.charAt(0)?.toUpperCase() ||
        props.tenantName?.charAt(0)?.toUpperCase() ||
        "T"
    );
});
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- SIDEBAR — sempre aberta e fixa -->
        <aside class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col bg-white border-r border-gray-200 shadow-sm">
            <!-- Header Sidebar -->
            <div class="p-5 border-b border-gray-200">
                <img v-if="tenantPublic?.logo" :src="tenantPublic?.logo" :alt="tenantName"
                    class="h-14 object-contain" />

                <div v-else class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-cyan-600 flex items-center justify-center shrink-0">
                        <span class="text-white text-base font-bold">
                            {{ tenantInitial }}
                        </span>
                    </div>

                    <div class="min-w-0">
                        <span class="block font-semibold text-gray-800 truncate">
                            {{ tenantName || tenantPublic?.name || "Tenant" }}
                        </span>
                        <p class="text-sm text-gray-400 truncate">
                            Painel do Tenant
                        </p>
                    </div>
                </div>
            </div>

            <!-- Navegação -->
            <nav class="flex-1 p-3 space-y-2 overflow-y-auto">
                <Link v-for="link in navLinks" :key="link.routeName" :href="route(link.routeName)" :class="[
                    'flex items-center gap-3 px-4 py-3.5 rounded-xl text-base font-medium border-l-4 transition-colors',
                    route().current(link.routeName)
                        ? 'bg-cyan-50 text-cyan-700 border-cyan-600 font-semibold'
                        : 'border-transparent text-gray-600 hover:bg-gray-100 hover:text-gray-900',
                ]">
                    <component :is="link.icon" class="w-6 h-6 shrink-0" />
                    <span>{{ link.label }}</span>
                </Link>
            </nav>

            <!-- Footer do Sidebar: Sair sempre visível -->
            <div class="p-3 border-t border-gray-200">
                <button type="button" @click="logout" :disabled="logoutForm.processing"
                    class="flex items-center gap-3 w-full px-4 py-3.5 rounded-xl text-base font-medium text-red-600 hover:bg-red-50 transition-colors disabled:opacity-60">
                    <LogOut class="w-6 h-6 shrink-0" />
                    <span v-if="logoutForm.processing">Saindo...</span>
                    <span v-else>Sair</span>
                </button>
            </div>
        </aside>

        <!-- MAIN -->
        <main class="min-h-screen ml-72">
            <!-- STATUS BAR SUPERIOR -->
            <header class="sticky top-0 z-20 bg-white border-b border-gray-200 px-6 py-3 shadow-sm">
                <div class="flex items-center justify-between">
                    <!-- Header slot -->
                    <div class="min-w-0">
                        <slot name="header" />
                    </div>

                    <!-- Lado direito -->
                    <div class="flex items-center gap-4">
                        <!-- Notificação -->
                        <button type="button"
                            class="relative p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                            <Bell class="w-5 h-5" />
                            <span
                                class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border-2 border-white" />
                        </button>

                        <div class="h-8 w-px bg-gray-200"></div>

                        <!-- User dropdown -->
                        <div class="relative">
                            <button type="button" @click="showUserMenu = !showUserMenu"
                                class="flex items-center gap-3 px-2 py-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                                <div
                                    class="w-8 h-8 rounded-full bg-gradient-to-br from-cyan-400 to-blue-500 flex items-center justify-center text-white text-sm font-bold">
                                    {{ userInitial }}
                                </div>

                                <div class="text-left hidden sm:block">
                                    <p class="text-sm font-medium text-gray-900 leading-tight">
                                        {{ authUser?.name || "Usuário" }}
                                    </p>
                                    <p class="text-xs text-gray-500 leading-tight">
                                        {{ authUser?.roles?.[0] || "Admin" }}
                                    </p>
                                </div>

                                <ChevronDown :class="[
                                    'w-4 h-4 text-gray-400 transition-transform',
                                    showUserMenu ? 'rotate-180' : '',
                                ]" />
                            </button>

                            <transition enter-active-class="transition ease-out duration-100"
                                enter-from-class="transform opacity-0 scale-95"
                                enter-to-class="transform opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-75"
                                leave-from-class="transform opacity-100 scale-100"
                                leave-to-class="transform opacity-0 scale-95">
                                <div v-if="showUserMenu"
                                    class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg border border-gray-200 py-2 z-50">
                                    <div class="px-4 py-3 border-b border-gray-100">
                                        <p class="text-sm font-semibold text-gray-900">
                                            {{ authUser?.name || "Usuário" }}
                                        </p>
                                        <p class="text-xs text-gray-500 mt-0.5">
                                            {{ authUser?.email || "Sem e-mail" }}
                                        </p>

                                        <div v-if="authUser?.roles?.length" class="flex flex-wrap gap-1 mt-2">
                                            <span v-for="role in authUser.roles" :key="role"
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium border"
                                                :class="{
                                                    'bg-purple-100 text-purple-700 border-purple-200': role === 'Admin',
                                                    'bg-blue-100 text-blue-700 border-blue-200': role === 'Manager',
                                                    'bg-green-100 text-green-700 border-green-200': role === 'Editor',
                                                    'bg-gray-100 text-gray-700 border-gray-200': role === 'User',
                                                }">
                                                <Shield class="w-3 h-3" />
                                                {{ role }}
                                            </span>
                                        </div>
                                    </div>

                                    <Link :href="route('perfil.edit')"
                                        class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                        <UserCircle class="w-4 h-4" />
                                        Meu Perfil
                                    </Link>
<!--
                                    <Link :href="route('tenant.dashboard')"
                                        class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                        <LayoutDashboard class="w-4 h-4" />
                                        Dashboard
                                    </Link> -->

                                    <div class="border-t border-gray-100 my-1"></div>

                                    <button type="button" @click="logout" :disabled="logoutForm.processing"
                                        class="flex items-center gap-2 w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors disabled:opacity-60">
                                        <LogOut class="w-4 h-4" />
                                        <span v-if="logoutForm.processing">
                                            Saindo...
                                        </span>
                                        <span v-else>Sair</span>
                                    </button>
                                </div>
                            </transition>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Conteúdo -->
            <div class="px-4 py-6 sm:px-6 sm:py-8 md:px-8 md:py-10">
                <slot />
            </div>
        </main>
    </div>
</template>
