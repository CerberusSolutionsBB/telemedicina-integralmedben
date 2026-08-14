<script setup>
import { Link, useForm, usePage } from "@inertiajs/vue3";
import {
    LayoutDashboard, Settings, BookMarked, Building2,
    ClipboardList, Users, MessageSquare, ScrollText, LandmarkIcon,
    BarChart3, LogOut, ChevronDown, AppWindowIcon,
    UserCircle, Bell, Shield, Key, Activity
} from "lucide-vue-next";
import { computed, ref } from "vue";

const page = usePage();
const authUser = computed(() => page.props.auth?.user);

const logoutForm = useForm({});
const expandedMenus = ref([]);
const showUserMenu = ref(false);

const logout = () => {
    logoutForm.post(route("logout"));
};

const toggleMenu = (key) => {
    const index = expandedMenus.value.indexOf(key);
    if (index === -1) {
        expandedMenus.value.push(key);
    } else {
        expandedMenus.value.splice(index, 1);
    }
};

const isMenuExpanded = (key) => expandedMenus.value.includes(key);

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
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- ════════════════════════════════════════ -->
        <!-- SIDEBAR (esquerda) — sempre aberta e fixa -->
        <!-- ════════════════════════════════════════ -->
        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col bg-white border-r border-gray-200 shadow-sm">
            <!-- Header -->
            <div class="p-5 border-b border-gray-200">
                <span class="text-xl font-bold text-gray-900 tracking-tight">
                    IntegralMedBen
                </span>
                <p class="text-sm text-gray-400 mt-0.5">Painel Administrativo</p>
            </div>

            <!-- Nav -->
            <nav class="flex-1 p-3 space-y-2 overflow-y-auto">
                <template v-for="link in navLinks" :key="link.routeName || link.key">

                    <!-- Link Simples -->
                    <Link v-if="!link.children" :href="route(link.routeName)" :class="[
                        'flex items-center gap-3 px-4 py-3.5 rounded-xl text-base font-medium border-l-4 transition-colors',
                        route().current(link.routeName)
                            ? 'bg-cyan-50 text-cyan-700 border-cyan-600 font-semibold'
                            : 'border-transparent text-gray-600 hover:bg-gray-100 hover:text-gray-900',
                    ]">
                        <component :is="link.icon" class="w-6 h-6 shrink-0" />
                        <span>{{ link.label }}</span>
                    </Link>

                    <!-- Menu com Submenu -->
                    <div v-else class="space-y-1">
                        <button type="button" @click="toggleMenu(link.key)" :class="[
                            'flex items-center justify-between gap-3 w-full px-4 py-3.5 rounded-xl text-base font-medium border-l-4 transition-colors',
                            isMenuExpanded(link.key) || link.children.some(c => route().current(c.routeName))
                                ? 'bg-cyan-50 text-cyan-700 border-cyan-600 font-semibold'
                                : 'border-transparent text-gray-600 hover:bg-gray-100 hover:text-gray-900',
                        ]">
                            <span class="flex items-center gap-3">
                                <component :is="link.icon" class="w-6 h-6 shrink-0" />
                                <span>{{ link.label }}</span>
                            </span>
                            <ChevronDown
                                :class="['w-5 h-5 shrink-0 transition-transform duration-200', isMenuExpanded(link.key) ? 'rotate-180' : '']" />
                        </button>

                        <transition enter-active-class="transition-all duration-200 ease-out"
                            enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition-all duration-150 ease-in"
                            leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
                            <div v-if="isMenuExpanded(link.key)" class="pl-6 space-y-1">
                                <Link v-for="child in link.children" :key="child.routeName"
                                    :href="route(child.routeName)" :class="[
                                        'flex items-center gap-3 px-4 py-3 rounded-lg text-sm transition-colors',
                                        route().current(child.routeName)
                                            ? 'bg-cyan-100 text-cyan-800 font-medium'
                                            : 'text-gray-500 hover:bg-gray-50 hover:text-gray-700',
                                    ]">
                                    <component :is="child.icon" class="w-5 h-5 shrink-0" />
                                    <span>{{ child.label }}</span>
                                </Link>
                            </div>
                        </transition>
                    </div>

                </template>
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

        <!-- ════════════════════════════════════════ -->
        <!-- MAIN CONTENT -->
        <!-- ════════════════════════════════════════ -->
        <main class="min-h-screen ml-72">

            <!-- ════════════════════════════════════════ -->
            <!-- ✅ BARRA DE STATUS SUPERIOR -->
            <!-- ════════════════════════════════════════ -->
            <header class="sticky top-0 z-20 bg-white border-b border-gray-200 px-6 py-3 shadow-sm">
                <div class="flex items-center justify-between">

                    <!-- Lado Esquerdo: Título da Página (slot) -->
                    <div>
                        <slot name="header" />
                    </div>

                    <!-- ════════════════════════════════════════ -->
                    <!-- ✅ LADO DIREITO: USUÁRIO LOGADO -->
                    <!-- ════════════════════════════════════════ -->
                    <div class="flex items-center gap-4">

                        <!-- Notificações -->
                        <button
                            class="relative p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                            <Bell class="w-5 h-5" />
                            <span
                                class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
                        </button>

                        <!-- Divider -->
                        <div class="h-8 w-px bg-gray-200"></div>

                        <!-- Dropdown do Usuário -->
                        <div class="relative">
                            <button @click="showUserMenu = !showUserMenu"
                                class="flex items-center gap-3 px-2 py-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                                <!-- Avatar -->
                                <div
                                    class="w-8 h-8 rounded-full bg-gradient-to-br from-cyan-400 to-blue-500 flex items-center justify-center text-white text-sm font-bold">
                                    {{ authUser?.name?.charAt(0).toUpperCase() || 'U' }}
                                </div>

                                <!-- Nome e Role -->
                                <div class="text-left hidden sm:block">
                                    <p class="text-sm font-medium text-gray-900 leading-tight">{{ authUser?.name }}</p>
                                    <p class="text-xs text-gray-500 leading-tight">{{ authUser?.roles?.[0] || 'Usuário'
                                    }}</p>
                                </div>

                                <ChevronDown
                                    :class="['w-4 h-4 text-gray-400 transition-transform', showUserMenu ? 'rotate-180' : '']" />
                            </button>

                            <!-- Menu Dropdown -->
                            <transition enter-active-class="transition ease-out duration-100"
                                enter-from-class="transform opacity-0 scale-95"
                                enter-to-class="transform opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-75"
                                leave-from-class="transform opacity-100 scale-100"
                                leave-to-class="transform opacity-0 scale-95">
                                <div v-if="showUserMenu"
                                    class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg border border-gray-200 py-2 z-50">
                                    <!-- Info do Usuário -->
                                    <div class="px-4 py-3 border-b border-gray-100">
                                        <p class="text-sm font-semibold text-gray-900">{{ authUser?.name }}</p>
                                        <p class="text-xs text-gray-500 mt-0.5">{{ authUser?.email }}</p>
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
                                    <Link :href="route('perfil.edit')"
                                        class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                        <UserCircle class="w-4 h-4" />
                                        Meu Perfil
                                    </Link>

                                    <Link :href="route('dashboard')"
                                        class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                        <LayoutDashboard class="w-4 h-4" />
                                        Dashboard
                                    </Link>

                                    <!-- Divider -->
                                    <div class="border-t border-gray-100 my-1"></div>

                                    <!-- Sair -->
                                    <button @click="logout" :disabled="logoutForm.processing"
                                        class="flex items-center gap-2 w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
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
            <div class="px-4 py-6 sm:px-6 sm:py-8 md:px-8 md:py-10">
                <slot />
            </div>
        </main>
    </div>
</template>
