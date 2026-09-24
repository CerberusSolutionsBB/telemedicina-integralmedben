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
    Settings,
    Menu,
    X,
} from "lucide-vue-next";
import { computed } from "vue";
import { useAdminLayout } from "@/Composables/useAdminLayout";

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

const {
    sidebarOpen,
    showUserMenu,
    userMenuRef,
    openSidebar,
    closeSidebar,
} = useAdminLayout();

const logout = () => {
    logoutForm.post(route("tenant.logout"));
};

const navLinks = [
    { label: "Beneficiários", routeName: "patients.index", icon: Users },
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

const displayName = computed(() => props.tenantName || tenantPublic.value?.name || "Tenant");

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
        <!-- Backdrop da gaveta (mobile) -->
        <transition enter-active-class="transition-opacity duration-200 ease-out" enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-150 ease-in" leave-to-class="opacity-0">
            <div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-gray-900/50 backdrop-blur-sm lg:hidden"
                aria-hidden="true" @click="closeSidebar" />
        </transition>

        <!-- SIDEBAR — fixa no desktop, gaveta no mobile -->
        <aside id="tenant-sidebar" :class="[
            'fixed inset-y-0 left-0 z-50 flex w-72 max-w-[85vw] flex-col bg-white border-r border-gray-200 shadow-xl lg:shadow-sm',
            'transition-transform duration-200 ease-out lg:translate-x-0',
            sidebarOpen ? 'translate-x-0' : '-translate-x-full',
        ]" aria-label="Menu principal">
            <!-- Header Sidebar -->
            <div class="flex items-start justify-between gap-2 p-5 border-b border-gray-200">
                <img v-if="tenantPublic?.logo" :src="tenantPublic?.logo" :alt="displayName"
                    class="h-12 max-w-[180px] object-contain" />

                <div v-else class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-lg bg-cyan-600 flex items-center justify-center shrink-0">
                        <span class="text-white text-base font-bold">
                            {{ tenantInitial }}
                        </span>
                    </div>

                    <div class="min-w-0">
                        <span class="block font-semibold text-gray-800 truncate">
                            {{ displayName }}
                        </span>
                        <p class="text-sm text-gray-400 truncate">
                            Painel do Tenant
                        </p>
                    </div>
                </div>

                <button type="button" @click="closeSidebar"
                    class="lg:hidden -mr-2 p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500"
                    aria-label="Fechar menu">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Navegação -->
            <nav class="flex-1 p-3 space-y-1 overflow-y-auto overscroll-contain">
                <Link v-for="link in navLinks" :key="link.routeName" :href="route(link.routeName)"
                    :aria-current="route().current(link.routeName) ? 'page' : undefined" :class="[
                        'flex items-center gap-3 px-4 py-3 min-h-[44px] rounded-xl text-[15px] font-medium border-l-4 transition-colors',
                        'focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500',
                        route().current(link.routeName)
                            ? 'bg-cyan-50 text-cyan-700 border-cyan-600 font-semibold'
                            : 'border-transparent text-gray-600 hover:bg-gray-100 hover:text-gray-900',
                    ]">
                    <component :is="link.icon" class="w-5 h-5 shrink-0" />
                    <span class="truncate">{{ link.label }}</span>
                </Link>
            </nav>

            <!-- Footer do Sidebar: Sair sempre visível -->
            <div class="p-3 border-t border-gray-200 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
                <button type="button" @click="logout" :disabled="logoutForm.processing"
                    class="flex items-center gap-3 w-full px-4 py-3 min-h-[44px] rounded-xl text-[15px] font-medium text-red-600 hover:bg-red-50 transition-colors disabled:opacity-60 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500">
                    <LogOut class="w-5 h-5 shrink-0" />
                    <span v-if="logoutForm.processing">Saindo...</span>
                    <span v-else>Sair</span>
                </button>
            </div>
        </aside>

        <!-- MAIN -->
        <main class="min-h-screen lg:ml-72">
            <!-- BARRA SUPERIOR -->
            <header class="sticky top-0 z-30 bg-white/95 backdrop-blur border-b border-gray-200 px-4 sm:px-6 py-2.5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <!-- Menu (mobile) + header slot -->
                    <div class="flex items-center gap-2 min-w-0">
                        <button type="button" @click="openSidebar"
                            class="lg:hidden -ml-2 p-2.5 rounded-lg text-gray-600 hover:bg-gray-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500"
                            aria-label="Abrir menu" aria-controls="tenant-sidebar" :aria-expanded="sidebarOpen">
                            <Menu class="w-6 h-6" />
                        </button>
                        <div class="min-w-0 truncate">
                            <slot name="header">
                                <span class="lg:hidden font-semibold text-gray-800">{{ displayName }}</span>
                            </slot>
                        </div>
                    </div>

                    <!-- Lado direito -->
                    <div class="flex items-center gap-1 sm:gap-3 shrink-0">
                        <!-- Notificação -->
                        <button type="button" aria-label="Notificações"
                            class="relative p-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500">
                            <Bell class="w-5 h-5" />
                            <span
                                class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full border-2 border-white" />
                        </button>

                        <div class="hidden sm:block h-8 w-px bg-gray-200"></div>

                        <!-- User dropdown -->
                        <div ref="userMenuRef" class="relative">
                            <button type="button" @click="showUserMenu = !showUserMenu"
                                :aria-expanded="showUserMenu" aria-haspopup="menu" aria-label="Menu do usuário"
                                class="flex items-center gap-2 sm:gap-3 p-1.5 sm:px-2 rounded-lg hover:bg-gray-100 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500">
                                <div
                                    class="w-8 h-8 rounded-full bg-gradient-to-br from-cyan-400 to-blue-500 flex items-center justify-center text-white text-sm font-bold">
                                    {{ userInitial }}
                                </div>

                                <div class="text-left hidden md:block max-w-[160px]">
                                    <p class="text-sm font-medium text-gray-900 leading-tight truncate">
                                        {{ authUser?.name || "Usuário" }}
                                    </p>
                                    <p class="text-xs text-gray-500 leading-tight truncate">
                                        {{ authUser?.roles?.[0] || "Admin" }}
                                    </p>
                                </div>

                                <ChevronDown :class="[
                                    'hidden sm:block w-4 h-4 text-gray-400 transition-transform',
                                    showUserMenu ? 'rotate-180' : '',
                                ]" />
                            </button>

                            <transition enter-active-class="transition ease-out duration-100"
                                enter-from-class="transform opacity-0 scale-95"
                                enter-to-class="transform opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-75"
                                leave-from-class="transform opacity-100 scale-100"
                                leave-to-class="transform opacity-0 scale-95">
                                <div v-if="showUserMenu" role="menu"
                                    class="absolute right-0 mt-2 w-64 max-w-[calc(100vw-2rem)] origin-top-right bg-white rounded-xl shadow-lg border border-gray-200 py-2 z-50">
                                    <div class="px-4 py-3 border-b border-gray-100">
                                        <p class="text-sm font-semibold text-gray-900 truncate">
                                            {{ authUser?.name || "Usuário" }}
                                        </p>
                                        <p class="text-xs text-gray-500 mt-0.5 truncate">
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

                                    <Link :href="route('perfil.edit')" role="menuitem"
                                        class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                        <UserCircle class="w-4 h-4" />
                                        Meu Perfil
                                    </Link>

                                    <div class="border-t border-gray-100 my-1"></div>

                                    <button type="button" role="menuitem" @click="logout" :disabled="logoutForm.processing"
                                        class="flex items-center gap-2 w-full px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors disabled:opacity-60">
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
            <div class="px-4 py-5 sm:px-6 sm:py-6 lg:px-8 lg:py-8">
                <slot />
            </div>
        </main>
    </div>
</template>
