<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import {
    Users,
    Target,
    ClipboardList,
    Shield,
    Settings,
    UserPlus,
    LayoutDashboard,
} from "lucide-vue-next";
import { computed } from "vue";
import AdminShell from "@/Components/Layout/AdminShell.vue";
import { useLogoRecortado } from "@/Composables/Layout/useLogoRecortado";

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

const tenantPublic = computed(() => page.props.tenant_public);
// Logo sem as margens vazias do arquivo, para ocupar a largura da sidebar.
const logo = useLogoRecortado(computed(() => tenantPublic.value?.logo));

// Controle de Acesso aponta para a primeira seção que o usuário pode ver
const userPermissions = computed(() => page.props.auth?.user?.permissions ?? []);
const aclLink = computed(() => {
    const section = [
        ["users", "acl.users.view"],
        ["roles", "acl.roles.view"],
        ["permissions", "acl.permissions.view"],
    ].find(([, permission]) => userPermissions.value.includes(permission));

    return section
        ? { grupo: "Administração", label: "Controle de Acesso", routeName: `tenant.acl.${section[0]}.index`, match: "tenant.acl.*", icon: Shield }
        : null;
});

const navLinks = computed(() => [
    { grupo: "Visão geral", label: "Dashboard", routeName: "tenant.dashboard", icon: LayoutDashboard },
    { grupo: "Beneficiários", label: "Beneficiários", routeName: "patients.index", icon: Users },
    { grupo: "Beneficiários", label: "Relatório", routeName: "desempenho.index", match: "desempenho.*", icon: Target },
    { grupo: "Beneficiários", label: "Meus Formulários", routeName: "meus-formularios.index", icon: ClipboardList },
    ...(aclLink.value ? [aclLink.value] : []),
    { grupo: "Administração", label: "Configurações", routeName: "configuracao.index", icon: Settings },
]);

// Card "Novo beneficiário" só quando o parceiro permite cadastrar.
const podeCadastrar = computed(() => page.props.beneficiarioPermissoes?.create ?? false);

const displayName = computed(() => props.tenantName || tenantPublic.value?.name || "Tenant");

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
    <AdminShell :menu="navLinks" logout-route="tenant.logout"
        :busca="{ rota: 'patients.index', placeholder: 'Buscar beneficiários por nome, CPF ou e-mail…' }">
        <template #marca>
            <Link :href="route('patients.index')" class="flex min-w-0 flex-1 items-center gap-3 px-1">
                <!-- Logo ocupa a largura da sidebar; a altura máxima evita logos muito altos. -->
                <img v-if="logo" :src="logo" :alt="displayName"
                    class="block h-auto w-full max-h-20 sm:max-h-24 object-contain object-left" />
                <template v-else>
                    <span class="grid w-10 h-10 shrink-0 place-items-center rounded-xl bg-cyan-600 text-base font-bold text-white">
                        {{ tenantInitial }}
                    </span>
                    <span class="min-w-0 leading-tight">
                        <span class="block truncate text-lg font-bold text-gray-900 dark:text-white">{{ displayName }}</span>
                        <span class="block truncate text-xs text-gray-500 dark:text-gray-400">Painel do Parceiro</span>
                    </span>
                </template>
            </Link>
        </template>

        <template v-if="podeCadastrar" #acao>
            <p class="text-sm font-semibold text-cyan-800 dark:text-cyan-200">Novo beneficiário?</p>
            <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">Cadastre e já escolha o plano do beneficiário.</p>
            <Link :href="route('patients.create')"
                class="mt-3 flex w-full items-center justify-center gap-2 rounded-lg bg-cyan-600 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-700 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 focus-visible:ring-offset-2">
                <UserPlus class="w-4 h-4" />
                Novo beneficiário
            </Link>
        </template>

        <template v-if="$slots.header" #header>
            <slot name="header" />
        </template>

        <slot />
    </AdminShell>
</template>
