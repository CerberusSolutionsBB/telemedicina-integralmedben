<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import {
    LayoutDashboard, Settings, BookMarked,
    ClipboardList, Users, ScrollText, LandmarkIcon,
    AppWindowIcon, HeartPulse, Plus,
    ShieldCheck, KeyRound, Activity,
} from "lucide-vue-next";
import { computed } from "vue";
import AdminShell from "@/Components/Layout/AdminShell.vue";

const page = usePage();

const allNavLinks = [
    { label: "Dashboard", routeName: "dashboard", icon: LayoutDashboard },
    { grupo: "Cadastros", label: "Página de Parceiros", routeName: "pagina.index", icon: AppWindowIcon },
    { grupo: "Cadastros", label: "Formulários", routeName: "forms.index", icon: ClipboardList },
    { grupo: "Cadastros", label: "Leis", routeName: "leis.index", icon: LandmarkIcon },
    { grupo: "Cadastros", label: "Telemedicina", routeName: "siprov.index", icon: Activity },
    // { label: "SMS Templates", routeName: "sms-templates.index", icon: MessageSquare },
    { grupo: "Monitoramento", label: "Logs de SMS", routeName: "admin.sms-logs.index", icon: ScrollText },
    {
        grupo: "Administração",
        label: "Controle de Acesso",
        key: "acl",
        icon: ShieldCheck,
        children: [
            { label: "Usuários", routeName: "acl.users.index", match: "acl.users.*", icon: Users, permission: "acl.users.view" },
            { label: "Perfis", routeName: "acl.roles.index", match: "acl.roles.*", icon: ShieldCheck, permission: "acl.roles.view" },
            { label: "Permissões", routeName: "acl.permissions.index", match: "acl.permissions.*", icon: KeyRound, permission: "acl.permissions.view" },
        ]
    },
    {
        grupo: "Administração",
        label: "Configurações",
        key: "configuracoes",
        icon: Settings,
        children: [
            { label: "Categorias de Formulários", routeName: "configuracoes.categories.forms.index", icon: BookMarked },
            { label: "Credencias Cluble", routeName: "configuracoes.credencias_cluble.index", icon: BookMarked },
        ]
    },
];

// Oculta itens que exigem permissão que o usuário não possui
const userPermissions = computed(() => page.props.auth?.user?.permissions ?? []);
const allowed = (item) => !item.permission || userPermissions.value.includes(item.permission);
const navLinks = computed(() =>
    allNavLinks
        .map((link) => (link.children ? { ...link, children: link.children.filter(allowed) } : link))
        .filter((link) => allowed(link) && (!link.children || link.children.length))
);
</script>

<template>
    <AdminShell :menu="navLinks" logout-route="logout"
        :busca="{ rota: 'pagina.index', placeholder: 'Buscar parceiros por nome, ID ou domínio…' }"
        :user-links="[{ label: 'Dashboard', routeName: 'dashboard', icon: LayoutDashboard }]">
        <template #marca>
            <Link :href="route('dashboard')" class="flex min-w-0 items-center gap-3 px-1">
                <span class="grid w-10 h-10 shrink-0 place-items-center rounded-xl bg-cyan-600 text-white">
                    <HeartPulse class="w-5 h-5" />
                </span>
                <span class="min-w-0 leading-tight">
                    <span class="block truncate text-lg font-bold text-gray-900 dark:text-white">IntegralMedBen</span>
                    <span class="block truncate text-xs text-gray-500 dark:text-gray-400">Painel Administrativo</span>
                </span>
            </Link>
        </template>

        <template #acao>
            <p class="text-sm font-semibold text-cyan-800 dark:text-cyan-200">Novo parceiro?</p>
            <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">Crie a página com formulário, planos e acesso do parceiro.</p>
            <Link :href="route('pagina.create')"
                class="mt-3 flex w-full items-center justify-center gap-2 rounded-lg bg-cyan-600 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-700 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 focus-visible:ring-offset-2">
                <Plus class="w-4 h-4" />
                Nova página
            </Link>
        </template>

        <template v-if="$slots.header" #header>
            <slot name="header" />
        </template>

        <slot />
    </AdminShell>
</template>
