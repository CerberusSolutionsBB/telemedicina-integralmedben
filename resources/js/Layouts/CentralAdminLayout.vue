<script setup>
import { Link, useForm, usePage } from "@inertiajs/vue3";
import {
    LayoutDashboard, Settings, BookMarked,
    ClipboardList, Users, ScrollText, LandmarkIcon,
    LogOut, ChevronDown, AppWindowIcon,
    UserCircle, Bell, Shield, Activity, Menu, X
} from "lucide-vue-next";
import { computed, ref } from "vue";
import { useAdminLayout } from "@/Composables/useAdminLayout";

const page = usePage();
const authUser = computed(() => page.props.auth?.user);

const logoutForm = useForm({});

const {
    sidebarOpen,
    showUserMenu,
    userMenuRef,
    openSidebar,
    closeSidebar,
} = useAdminLayout();

const logout = () => {
    logoutForm.post(route("logout"));
};

const navLinks = [
    { label: "Dashboard", routeName: "dashboard", icon: LayoutDashboard },
    { label: "Formulários", routeName: "forms.index", icon: ClipboardList },
    { label: "Leis", routeName: "leis.index", icon: LandmarkIcon },
    { label: "Usuários", routeName: "central-users.index", icon: Users },
    // { label: "SMS Templates", routeName: "sms-templates.index", icon: MessageSquare },
    { label: "Logs de SMS", routeName: "admin.sms-logs.index", icon: ScrollText },
    { label: "Página de Parceiros", routeName: "pagina.index", icon: AppWindowIcon },
    { label: "Telemedicina", routeName: "siprov.index", icon: Activity },
    {
        label: "Configurações",
        key: "configuracoes",
        icon: Settings,
        children: [
            { label: "Categorias de Formulários", routeName: "configuracoes.categories.forms.index", icon: BookMarked },
            { label: "Credencias Cluble", routeName: "configuracoes.credencias_cluble.index", icon: BookMarked },
        ]
    },
];

const isChildActive = (link) => link.children?.some((c) => route().current(c.routeName));

// Submenu da página atual já começa aberto.
const expandedMenus = ref(navLinks.filter(isChildActive).map((l) => l.key));

const toggleMenu = (key) => {
    const index = expandedMenus.value.indexOf(key);
    if (index === -1) {
        expandedMenus.value.push(key);
    } else {
        expandedMenus.value.splice(index, 1);
    }
};

const isMenuExpanded = (key) => expandedMenus.value.includes(key);

const userInitial = computed(() => authUser.value?.name?.charAt(0)?.toUpperCase() || "U");
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Backdrop da gaveta (mobile) -->
        <transition enter-active-class="transition-opacity duration-200 ease-out" enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-150 ease-in" leave-to-class="opacity-0">
            <div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-gray-900/50 backdrop-blur-sm lg:hidden"
                aria-hidden="true" @click="closeSidebar" />
        </transition>

        <!-- ════════════════════════════════════════ -->
        <!-- SIDEBAR — fixa no desktop, gaveta no mobile -->
        <!-- ════════════════════════════════════════ -->
        <aside id="admin-sidebar" :class="[
            'fixed inset-y-0 left-0 z-50 flex w-72 max-w-[85vw] flex-col bg-white border-r border-gray-200 shadow-xl lg:shadow-sm',
            'transition-transform duration-200 ease-out lg:translate-x-0',
            sidebarOpen ? 'translate-x-0' : '-translate-x-full',
        ]" aria-label="Menu principal">
            <!-- Header -->
            <div class="flex items-start justify-between gap-2 p-5 border-b border-gray-200">
                <div class="min-w-0">
                    <span class="block text-xl font-bold text-gray-900 tracking-tight truncate">
                        IntegralMedBen
                    </span>
                    <p class="text-sm text-gray-400 mt-0.5">Painel Administrativo</p>
                </div>
                <button type="button" @click="closeSidebar"
                    class="lg:hidden -mr-2 p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500"
                    aria-label="Fechar menu">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Nav -->
            <nav class="flex-1 p-3 space-y-1 overflow-y-auto overscroll-contain">
                <template v-for="link in navLinks" :key="link.routeName || link.key">

                    <!-- Link Simples -->
                    <Link v-if="!link.children" :href="route(link.routeName)"
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

                    <!-- Menu com Submenu -->
                    <div v-else class="space-y-1">
                        <button type="button" @click="toggleMenu(link.key)" :aria-expanded="isMenuExpanded(link.key)"
                            :aria-controls="'submenu-' + link.key" :class="[
                                'flex items-center justify-between gap-3 w-full px-4 py-3 min-h-[44px] rounded-xl text-[15px] font-medium border-l-4 transition-colors',
                                'focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500',
                                isChildActive(link)
                                    ? 'bg-cyan-50 text-cyan-700 border-cyan-600 font-semibold'
                                    : 'border-transparent text-gray-600 hover:bg-gray-100 hover:text-gray-900',
                            ]">
                            <span class="flex items-center gap-3 min-w-0">
                                <component :is="link.icon" class="w-5 h-5 shrink-0" />
                                <span class="truncate">{{ link.label }}</span>
                            </span>
                            <ChevronDown
                                :class="['w-4 h-4 shrink-0 transition-transform duration-200', isMenuExpanded(link.key) ? 'rotate-180' : '']" />
                        </button>

                        <transition enter-active-class="transition-all duration-200 ease-out"
                            enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition-all duration-150 ease-in"
                            leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
                            <div v-if="isMenuExpanded(link.key)" :id="'submenu-' + link.key"
                                class="ml-6 pl-3 border-l border-gray-100 space-y-1">
                                <Link v-for="child in link.children" :key="child.routeName"
                                    :href="route(child.routeName)"
                                    :aria-current="route().current(child.routeName) ? 'page' : undefined" :class="[
                                        'flex items-center gap-3 px-3 py-2.5 min-h-[44px] rounded-lg text-sm transition-colors',
                                        'focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500',
                                        route().current(child.routeName)
                                            ? 'bg-cyan-100 text-cyan-800 font-medium'
                                            : 'text-gray-500 hover:bg-gray-50 hover:text-gray-700',
                                    ]">
                                    <component :is="child.icon" class="w-4 h-4 shrink-0" />
                                    <span class="truncate">{{ child.label }}</span>
                                </Link>
                            </div>
                        </transition>
                    </div>

                </template>
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

        <!-- ════════════════════════════════════════ -->
        <!-- MAIN CONTENT -->
        <!-- ════════════════════════════════════════ -->
        <main class="min-h-screen lg:ml-72">

            <!-- BARRA SUPERIOR -->
            <header class="sticky top-0 z-30 bg-white/95 backdrop-blur border-b border-gray-200 px-4 sm:px-6 py-2.5 shadow-sm">
                <div class="flex items-center justify-between gap-3">

                    <!-- Lado Esquerdo: menu (mobile) + título da página (slot) -->
                    <div class="flex items-center gap-2 min-w-0">
                        <button type="button" @click="openSidebar"
                            class="lg:hidden -ml-2 p-2.5 rounded-lg text-gray-600 hover:bg-gray-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500"
                            aria-label="Abrir menu" aria-controls="admin-sidebar" :aria-expanded="sidebarOpen">
                            <Menu class="w-6 h-6" />
                        </button>
                        <div class="min-w-0 truncate">
                            <slot name="header">
                                <span class="lg:hidden font-bold text-gray-900 tracking-tight">IntegralMedBen</span>
                            </slot>
                        </div>
                    </div>

                    <!-- LADO DIREITO: USUÁRIO LOGADO -->
                    <div class="flex items-center gap-1 sm:gap-3 shrink-0">

                        <!-- Notificações -->
                        <button type="button" aria-label="Notificações"
                            class="relative p-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500">
                            <Bell class="w-5 h-5" />
                            <span
                                class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
                        </button>

                        <!-- Divider -->
                        <div class="hidden sm:block h-8 w-px bg-gray-200"></div>

                        <!-- Dropdown do Usuário -->
                        <div ref="userMenuRef" class="relative">
                            <button type="button" @click="showUserMenu = !showUserMenu"
                                :aria-expanded="showUserMenu" aria-haspopup="menu" aria-label="Menu do usuário"
                                class="flex items-center gap-2 sm:gap-3 p-1.5 sm:px-2 rounded-lg hover:bg-gray-100 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500">
                                <!-- Avatar -->
                                <div
                                    class="w-8 h-8 rounded-full bg-gradient-to-br from-cyan-400 to-blue-500 flex items-center justify-center text-white text-sm font-bold">
                                    {{ userInitial }}
                                </div>

                                <!-- Nome e Role -->
                                <div class="text-left hidden md:block max-w-[160px]">
                                    <p class="text-sm font-medium text-gray-900 leading-tight truncate">{{ authUser?.name }}</p>
                                    <p class="text-xs text-gray-500 leading-tight truncate">{{ authUser?.roles?.[0] || 'Usuário' }}</p>
                                </div>

                                <ChevronDown
                                    :class="['hidden sm:block w-4 h-4 text-gray-400 transition-transform', showUserMenu ? 'rotate-180' : '']" />
                            </button>

                            <!-- Menu Dropdown -->
                            <transition enter-active-class="transition ease-out duration-100"
                                enter-from-class="transform opacity-0 scale-95"
                                enter-to-class="transform opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-75"
                                leave-from-class="transform opacity-100 scale-100"
                                leave-to-class="transform opacity-0 scale-95">
                                <div v-if="showUserMenu" role="menu"
                                    class="absolute right-0 mt-2 w-64 max-w-[calc(100vw-2rem)] origin-top-right bg-white rounded-xl shadow-lg border border-gray-200 py-2 z-50">
                                    <!-- Info do Usuário -->
                                    <div class="px-4 py-3 border-b border-gray-100">
                                        <p class="text-sm font-semibold text-gray-900 truncate">{{ authUser?.name }}</p>
                                        <p class="text-xs text-gray-500 mt-0.5 truncate">{{ authUser?.email }}</p>
                                        <div class="flex flex-wrap gap-1 mt-2">
                                            <span v-for="role in authUser?.roles" :key="role"
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

                                    <!-- Links -->
                                    <Link :href="route('perfil.edit')" role="menuitem"
                                        class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                        <UserCircle class="w-4 h-4" />
                                        Meu Perfil
                                    </Link>

                                    <Link :href="route('dashboard')" role="menuitem"
                                        class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                        <LayoutDashboard class="w-4 h-4" />
                                        Dashboard
                                    </Link>

                                    <!-- Divider -->
                                    <div class="border-t border-gray-100 my-1"></div>

                                    <!-- Sair -->
                                    <button type="button" role="menuitem" @click="logout" :disabled="logoutForm.processing"
                                        class="flex items-center gap-2 w-full px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors disabled:opacity-60">
                                        <LogOut class="w-4 h-4" />
                                        <span v-if="logoutForm.processing">Saindo...</span>
                                        <span v-else>Sair</span>
                                    </button>
                                </div>
                            </transition>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Conteúdo Principal -->
            <div class="px-4 py-5 sm:px-6 sm:py-6 lg:px-8 lg:py-8">
                <slot />
            </div>
        </main>
    </div>
</template>
