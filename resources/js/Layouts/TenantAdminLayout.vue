<script setup>
import { Link, useForm, usePage } from "@inertiajs/vue3";
import {
    Users,
    UserCircle,
    LogOut,
    ChevronRight,
    ChevronDown,
    ClipboardList,
    Shield,
    Settings,
    PanelLeftOpen,
    PanelLeftClose,
} from "lucide-vue-next";
import { ref, computed, onMounted, onUnmounted } from "vue";

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

const open = ref(true);
const showUserMenu = ref(false);

const logout = () => {
    logoutForm.post(route("tenant.logout"));
};

const closeUserMenu = () => {
    showUserMenu.value = false;
};

const handleKeydown = (e) => {
    if (e.key === "Escape") {
        showUserMenu.value = false;
    }
};

onMounted(() => window.addEventListener("keydown", handleKeydown));
onUnmounted(() => window.removeEventListener("keydown", handleKeydown));

const navLinks = [
    { label: "Pacientes", routeName: "patients.index", icon: Users },
    { label: "Usuários", routeName: "users.index", icon: UserCircle },
    { label: "Meus Formulários", routeName: "meus-formularios.index", icon: ClipboardList },
    { label: "Configurações", routeName: "configuracao.index", icon: Settings },
];

const userInitial = computed(() => authUser.value?.name?.charAt(0)?.toUpperCase() || "U");

const tenantInitial = computed(() =>
    tenantPublic.value?.detail?.sigla ||
    tenantPublic.value?.slug?.charAt(0)?.toUpperCase() ||
    props.tenantName?.charAt(0)?.toUpperCase() ||
    "T"
);

const isActive = (routeName) => route().current(routeName);
</script>

<template>
    <div class="min-h-screen bg-gray-50/50">
        <!-- Backdrop mobile -->
        <transition name="fade">
            <div v-if="open" class="fixed inset-0 z-30 bg-black/20 lg:hidden" @click="open = false" />
        </transition>

        <!-- SIDEBAR -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-40 flex flex-col bg-white border-r border-gray-200 transition-all duration-300 ease-in-out shadow-sm',
                open ? 'w-60' : 'w-[68px]',
            ]">
            <!-- Toggle -->
            <button
                @click="open = !open"
                class="absolute -right-3.5 top-5 z-10 w-7 h-7 bg-white border border-gray-300 rounded-full flex items-center justify-center shadow-sm hover:bg-gray-50 hover:border-gray-400 transition-all"
                :title="open ? 'Recolher menu' : 'Expandir menu'">
                <PanelLeftClose v-if="open" class="w-3.5 h-3.5 text-gray-500" />
                <PanelLeftOpen v-else class="w-3.5 h-3.5 text-gray-500" />
            </button>

            <!-- Header -->
            <div class="p-4 border-b border-gray-100 overflow-hidden flex-shrink-0">
                <template v-if="open">
                    <img v-if="tenantPublic?.logo" :src="tenantPublic?.logo" :alt="tenantName"
                        class="h-10 object-contain mx-auto" />
                    <div v-else class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center shrink-0 shadow-sm">
                            <span class="text-white text-sm font-bold">{{ tenantInitial }}</span>
                        </div>
                        <div class="min-w-0">
                            <span class="block text-sm font-bold text-gray-800 truncate">
                                {{ tenantName || tenantPublic?.name || "Tenant" }}
                            </span>
                            <p class="text-[10px] text-gray-400 truncate">Painel</p>
                        </div>
                    </div>
                </template>
                <template v-else>
                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center mx-auto shadow-sm">
                        <span class="text-white text-sm font-bold">{{ tenantInitial }}</span>
                    </div>
                </template>
            </div>

            <!-- Navegação -->
            <nav class="flex-1 p-2 space-y-0.5 overflow-y-auto overflow-x-hidden">
                <Link
                    v-for="link in navLinks"
                    :key="link.routeName"
                    :href="route(link.routeName)"
                    :title="!open ? link.label : undefined"
                    @click="open = false"
                    :class="[
                        'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 border-l-[3px]',
                        !open ? 'justify-center border-l-transparent' : '',
                        isActive(link.routeName)
                            ? 'bg-cyan-50 text-cyan-700 border-l-cyan-500'
                            : 'text-gray-600 border-l-transparent hover:bg-gray-100 hover:text-gray-800 hover:border-l-gray-300',
                    ]">
                    <component :is="link.icon"
                        class="w-5 h-5 shrink-0"
                        :class="isActive(link.routeName) ? 'text-cyan-600' : 'text-gray-400 group-hover:text-gray-600'" />
                    <span v-if="open" class="whitespace-nowrap truncate">{{ link.label }}</span>
                </Link>
            </nav>

            <!-- Footer colapsado -->
            <div v-if="!open" class="p-2 border-t border-gray-100 flex-shrink-0">
                <button @click="logout" :disabled="logoutForm.processing"
                    title="Sair"
                    class="flex items-center justify-center w-full p-2 rounded-lg text-red-500 hover:bg-red-50 transition-colors disabled:opacity-50">
                    <LogOut class="w-5 h-5" />
                </button>
            </div>
        </aside>

        <!-- MAIN -->
        <main :class="['min-h-screen transition-all duration-300', open ? 'lg:ml-60' : 'lg:ml-[68px]']">
            <!-- Top bar -->
            <header class="sticky top-0 z-20 bg-white/80 backdrop-blur-sm border-b border-gray-200 px-4 sm:px-6 py-2.5 flex items-center justify-between">
                <div class="min-w-0">
                    <slot name="header" />
                </div>

                <div class="flex items-center gap-3 ml-4">
                    <!-- User -->
                    <div class="relative">
                        <button type="button" @click="showUserMenu = !showUserMenu"
                            class="flex items-center gap-2.5 px-2 py-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-cyan-400 to-blue-500 flex items-center justify-center text-white text-sm font-bold shadow-sm">
                                {{ userInitial }}
                            </div>
                            <div class="text-left hidden sm:block">
                                <p class="text-sm font-medium text-gray-800 leading-tight">{{ authUser?.name || "Usuário" }}</p>
                                <p class="text-[11px] text-gray-500 leading-tight">{{ authUser?.roles?.[0] || "Admin" }}</p>
                            </div>
                            <ChevronDown :class="['w-4 h-4 text-gray-400 transition-transform', showUserMenu ? 'rotate-180' : '']" />
                        </button>

                        <!-- Dropdown -->
                        <transition
                            enter-active-class="transition ease-out duration-150"
                            enter-from-class="transform opacity-0 scale-95 -translate-y-1"
                            enter-to-class="transform opacity-100 scale-100 translate-y-0"
                            leave-active-class="transition ease-in duration-100"
                            leave-from-class="transform opacity-100 scale-100"
                            leave-to-class="transform opacity-0 scale-95">
                            <div v-if="showUserMenu"
                                class="absolute right-0 mt-2 w-60 bg-white rounded-xl shadow-lg border border-gray-200 py-2 z-50"
                                @click="closeUserMenu">
                                <div class="px-4 py-3 border-b border-gray-100">
                                    <p class="text-sm font-semibold text-gray-900">{{ authUser?.name || "Usuário" }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ authUser?.email || "Sem e-mail" }}</p>
                                    <div v-if="authUser?.roles?.length" class="flex flex-wrap gap-1 mt-2">
                                        <span v-for="role in authUser.roles" :key="role"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border"
                                            :class="{
                                                'bg-purple-100 text-purple-700 border-purple-200': role === 'Admin',
                                                'bg-blue-100 text-blue-700 border-blue-200': role === 'Manager',
                                                'bg-green-100 text-green-700 border-green-200': role === 'Editor',
                                                'bg-gray-100 text-gray-600 border-gray-200': role !== 'Admin' && role !== 'Manager' && role !== 'Editor',
                                            }">
                                            <Shield class="w-3 h-3" />
                                            {{ role }}
                                        </span>
                                    </div>
                                </div>

                                <a :href="route('perfil.edit')"
                                    class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                    <UserCircle class="w-4 h-4 text-gray-400" />
                                    Meu Perfil
                                </a>

                                <div class="border-t border-gray-100 my-1"></div>

                                <button type="button" @click="logout" :disabled="logoutForm.processing"
                                    class="flex items-center gap-2.5 w-full px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors disabled:opacity-50">
                                    <LogOut class="w-4 h-4" />
                                    <span v-if="logoutForm.processing">Saindo...</span>
                                    <span v-else>Sair</span>
                                </button>
                            </div>
                        </transition>
                    </div>
                </div>
            </header>

            <!-- Conteúdo -->
            <div class="p-4 sm:p-6">
                <slot />
            </div>
        </main>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
