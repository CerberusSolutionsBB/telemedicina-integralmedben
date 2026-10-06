<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { CheckCircle2, ChevronDown, LogOut, Menu, Search, ShieldCheck, UserCircle, X, XCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ThemeSwitcher from '@/Components/Layout/ThemeSwitcher.vue';
import { useAdminLayout } from '@/Composables/useAdminLayout';
import { useBuscaGlobal } from '@/Composables/Layout/useBuscaGlobal';
import { useFlashAlertas } from '@/Composables/Layout/useFlashAlertas';

/**
 * Estrutura comum dos painéis (central e tenant): sidebar com marca, card do
 * painel, menu por grupos e card de ação; topbar com busca, tema e usuário;
 * alertas de flash acima do conteúdo.
 */
const props = defineProps({
    // [{ label, routeName, match?, icon, grupo?, key?, children?: [{ label, routeName, match?, icon }] }]
    menu: { type: Array, required: true },
    // { rota, placeholder }
    busca: { type: Object, required: true },
    logoutRoute: { type: String, required: true },
    // Links extras do menu do usuário: [{ label, routeName, icon }]
    userLinks: { type: Array, default: () => [] },
});

const page = usePage();
const authUser = computed(() => page.props.auth?.user);
const roles = computed(() => authUser.value?.roles ?? []);
const iniciais = computed(() =>
    (authUser.value?.name || 'U')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((parte) => parte[0].toUpperCase())
        .join(''),
);
const ano = new Date().getFullYear();

const { sidebarOpen, showUserMenu, userMenuRef, openSidebar, closeSidebar } = useAdminLayout();
const { campo: campoBusca, termo: termoBusca, atalho: atalhoBusca, buscar } = useBuscaGlobal(props.busca.rota);
const { alertas, fechar: fecharAlerta } = useFlashAlertas();

const logoutForm = useForm({});
const sair = () => logoutForm.post(route(props.logoutRoute));

const ativo = (item) => route().current(item.match || item.routeName);
const filhoAtivo = (item) => item.children?.some(ativo);

// Submenu da página atual já começa aberto.
const abertos = ref(props.menu.filter(filhoAtivo).map((item) => item.key));
const aberto = (key) => abertos.value.includes(key);
const alternarSubmenu = (key) => {
    abertos.value = aberto(key) ? abertos.value.filter((k) => k !== key) : [...abertos.value, key];
};

const itemClasse = (selecionado) => [
    'flex items-center gap-3 w-full px-3 py-2.5 min-h-[44px] rounded-xl text-[15px] font-medium transition-colors',
    'focus:outline-none focus-visible:ring-2 focus-visible:ring-[#23BACF]',
    selecionado
        ? 'bg-[#23BACF]/10 text-[#23BACF] font-semibold dark:bg-[#23BACF]/15 dark:text-[#23BACF]'
        : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white',
];
</script>

<template>
    <div class="min-h-screen bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-100">
        <!-- Backdrop da gaveta (mobile) -->
        <transition enter-active-class="transition-opacity duration-200 ease-out" enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-150 ease-in" leave-to-class="opacity-0">
            <div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-gray-900/50 backdrop-blur-sm lg:hidden"
                aria-hidden="true" @click="closeSidebar" />
        </transition>

        <!-- Sidebar: fixa no desktop, gaveta no mobile -->
        <aside id="admin-sidebar" :class="[
            'fixed inset-y-0 left-0 z-50 flex w-72 max-w-[85vw] flex-col gap-5 overflow-y-auto overscroll-contain p-4',
            'bg-white border-r border-gray-200 shadow-xl lg:shadow-none dark:bg-gray-900 dark:border-gray-800',
            'transition-transform duration-200 ease-out lg:translate-x-0',
            sidebarOpen ? 'translate-x-0' : '-translate-x-full',
        ]" aria-label="Menu principal">
            <div class="flex items-start justify-between gap-2">
                <slot name="marca" />
                <button type="button" @click="closeSidebar"
                    class="lg:hidden -mr-1 p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#23BACF]"
                    aria-label="Fechar menu">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Card do painel -->
            <div
                class="flex items-center gap-3 rounded-xl bg-[#23BACF] p-3 text-white shadow-md">
                <span class="grid w-9 h-9 shrink-0 place-items-center rounded-lg bg-white/15">
                    <ShieldCheck class="w-5 h-5" />
                </span>
                <span class="min-w-0 leading-tight">
                    <span class="block truncate text-sm font-semibold">Painel {{ roles[0] ?? '' }}</span>
                    <span class="block text-xs text-white/75">{{ ano }}</span>
                </span>
            </div>

            <!-- Menu por grupos -->
            <nav class="flex-1 space-y-1">
                <template v-for="(item, indice) in menu" :key="item.routeName || item.key">
                    <p v-if="item.grupo && item.grupo !== menu[indice - 1]?.grupo"
                        class="px-3 pt-3 pb-1 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                        {{ item.grupo }}
                    </p>

                    <Link v-if="!item.children" :href="route(item.routeName)"
                        :aria-current="ativo(item) ? 'page' : undefined" :class="itemClasse(ativo(item))">
                        <component :is="item.icon" class="w-5 h-5 shrink-0" />
                        <span class="truncate">{{ item.label }}</span>
                    </Link>

                    <div v-else>
                        <button type="button" @click="alternarSubmenu(item.key)" :aria-expanded="aberto(item.key)"
                            :aria-controls="'submenu-' + item.key" :class="[itemClasse(filhoAtivo(item)), 'justify-between']">
                            <span class="flex items-center gap-3 min-w-0">
                                <component :is="item.icon" class="w-5 h-5 shrink-0" />
                                <span class="truncate">{{ item.label }}</span>
                            </span>
                            <ChevronDown
                                :class="['w-4 h-4 shrink-0 transition-transform duration-200', aberto(item.key) ? 'rotate-180' : '']" />
                        </button>
                        <div v-if="aberto(item.key)" :id="'submenu-' + item.key"
                            class="ml-5 mt-1 pl-3 border-l border-gray-200 dark:border-gray-800 space-y-1">
                            <Link v-for="filho in item.children" :key="filho.routeName" :href="route(filho.routeName)"
                                :aria-current="ativo(filho) ? 'page' : undefined" :class="[
                                    'flex items-center gap-3 px-3 py-2 min-h-[40px] rounded-lg text-sm transition-colors',
                                    'focus:outline-none focus-visible:ring-2 focus-visible:ring-[#23BACF]',
                                    ativo(filho)
                                        ? 'bg-[#23BACF]/10 text-[#23BACF] font-medium dark:bg-[#23BACF]/15 dark:text-[#23BACF]'
                                        : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200',
                                ]">
                                <component :is="filho.icon" class="w-4 h-4 shrink-0" />
                                <span class="truncate">{{ filho.label }}</span>
                            </Link>
                        </div>
                    </div>
                </template>
            </nav>

            <!-- Card de ação no rodapé -->
            <div v-if="$slots.acao"
                class="rounded-xl border border-[#23BACF]/20 bg-[#23BACF]/5 p-4 dark:border-[#23BACF]/30 dark:bg-[#23BACF]/10">
                <slot name="acao" />
            </div>
        </aside>

        <div class="flex min-h-screen min-w-0 flex-col lg:ml-72">
            <!-- Topbar -->
            <header
                class="sticky top-0 z-30 flex items-center gap-2 sm:gap-3 border-b border-gray-200 bg-white/90 px-4 py-2.5 backdrop-blur lg:px-6 dark:border-gray-800 dark:bg-gray-900/90">
                <button type="button" @click="openSidebar"
                    class="lg:hidden -ml-2 p-2.5 rounded-lg text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#23BACF]"
                    aria-label="Abrir menu" aria-controls="admin-sidebar" :aria-expanded="sidebarOpen">
                    <Menu class="w-6 h-6" />
                </button>

                <form class="flex-1 min-w-0" role="search" @submit.prevent="buscar">
                    <label
                        class="flex w-full max-w-xl items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 focus-within:border-[#23BACF] focus-within:ring-2 focus-within:ring-[#23BACF] dark:border-gray-700 dark:bg-gray-800">
                        <Search class="w-4 h-4 shrink-0 text-gray-400" />
                        <input ref="campoBusca" v-model="termoBusca" type="search" :placeholder="props.busca.placeholder"
                            :aria-label="props.busca.placeholder"
                            class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm text-gray-900 placeholder-gray-400 focus:ring-0 dark:text-gray-100" />
                        <kbd
                            class="hidden sm:inline-flex shrink-0 rounded border border-gray-200 bg-white px-1.5 py-0.5 font-sans text-[11px] text-gray-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-400">
                            {{ atalhoBusca }}
                        </kbd>
                    </label>
                </form>

                <ThemeSwitcher />

                <div class="hidden sm:block h-8 w-px bg-gray-200 dark:bg-gray-700"></div>

                <!-- Usuário -->
                <div ref="userMenuRef" class="relative shrink-0">
                    <button type="button" @click="showUserMenu = !showUserMenu" :aria-expanded="showUserMenu"
                        aria-haspopup="menu" aria-label="Menu do usuário"
                        class="flex items-center gap-3 rounded-lg p-1.5 sm:px-2 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#23BACF]">
                        <img v-if="authUser?.avatar" :src="authUser.avatar" alt=""
                            class="w-9 h-9 shrink-0 rounded-full object-cover" />
                        <span v-else
                            class="grid w-9 h-9 shrink-0 place-items-center rounded-full bg-[#23BACF] text-xs font-bold text-white">
                            {{ iniciais }}
                        </span>
                        <span class="hidden md:flex max-w-[180px] flex-col items-start leading-tight">
                            <span class="w-full truncate text-sm font-semibold">{{ authUser?.name || 'Usuário' }}</span>
                            <span class="w-full truncate text-xs text-gray-500 dark:text-gray-400">
                                {{ roles.join(', ') || 'Usuário' }}
                            </span>
                        </span>
                        <ChevronDown
                            :class="['hidden sm:block w-4 h-4 text-gray-400 transition-transform', showUserMenu ? 'rotate-180' : '']" />
                    </button>

                    <transition enter-active-class="transition ease-out duration-100"
                        enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75"
                        leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                        <div v-if="showUserMenu" role="menu"
                            class="absolute right-0 z-50 mt-2 w-56 max-w-[calc(100vw-2rem)] origin-top-right rounded-xl border border-gray-200 bg-white py-2 shadow-lg dark:border-gray-700 dark:bg-gray-900">
                            <p class="truncate px-4 pb-2 pt-1 text-xs text-gray-500 dark:text-gray-400">{{ authUser?.email }}</p>
                            <Link v-for="link in [{ label: 'Meu Perfil', routeName: 'perfil.edit', icon: UserCircle }, ...userLinks]"
                                :key="link.routeName" :href="route(link.routeName)" role="menuitem"
                                class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800 transition-colors">
                                <component :is="link.icon" class="w-4 h-4" />
                                {{ link.label }}
                            </Link>
                            <div class="my-1 border-t border-gray-100 dark:border-gray-800"></div>
                            <button type="button" role="menuitem" @click="sair" :disabled="logoutForm.processing"
                                class="flex w-full items-center gap-2 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10 transition-colors disabled:opacity-60">
                                <LogOut class="w-4 h-4" />
                                {{ logoutForm.processing ? 'Saindo...' : 'Sair' }}
                            </button>
                        </div>
                    </transition>
                </div>
            </header>

            <!-- Alertas (flash): fixos abaixo da topbar para aparecerem mesmo com a página rolada -->
            <div v-if="alertas.length" class="sticky top-[61px] z-20 space-y-2 px-4 pt-4 lg:px-6" aria-live="polite">
                <div v-for="alerta in alertas" :key="alerta.id" role="alert" :class="[
                    'flex items-start gap-3 rounded-xl border px-4 py-3 text-sm shadow-sm',
                    alerta.tipo === 'success'
                        ? 'border-green-200 bg-green-50 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200'
                        : 'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200',
                ]">
                    <CheckCircle2 v-if="alerta.tipo === 'success'" class="mt-0.5 w-4 h-4 shrink-0" />
                    <XCircle v-else class="mt-0.5 w-4 h-4 shrink-0" />
                    <span class="flex-1">{{ alerta.mensagem }}</span>
                    <button type="button" @click="fecharAlerta(alerta.id)" aria-label="Fechar aviso"
                        class="-m-1 p-1 rounded opacity-70 hover:opacity-100">
                        <X class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <main class="flex-1 px-4 py-5 sm:px-6 sm:py-6 lg:px-8">
                <div v-if="$slots.header" class="mb-5">
                    <slot name="header" />
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>
