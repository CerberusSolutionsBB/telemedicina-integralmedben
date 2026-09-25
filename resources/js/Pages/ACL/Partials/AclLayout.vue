<script setup>
import { computed, watch } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import { Home, KeyRound, ShieldCheck, Users } from "lucide-vue-next";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import CentralAdminLayout from "@/Layouts/CentralAdminLayout.vue";
import TenantAdminLayout from "@/Layouts/TenantAdminLayout.vue";
import { showToast } from "@/Utils/toast";
import { useAcl } from "./useAcl";

const props = defineProps({
    title: { type: String, required: true },
    description: { type: String, default: "" },
    // Itens após "Controle de Acesso": [{ label, route?, params? }] — o último é a tela atual
    breadcrumbs: { type: Array, default: () => [] },
});

const page = usePage();
const { acl, aclRoute, can } = useAcl();

const isTenant = computed(() => acl.value.context === "tenant");
const layout = computed(() => (isTenant.value ? TenantAdminLayout : CentralAdminLayout));
const layoutProps = computed(() =>
    isTenant.value ? { tenantName: acl.value.tenantName || "", tenantPhoto: acl.value.tenantPhoto } : {}
);

const tabs = computed(() =>
    [
        { label: "Usuários", route: "users.index", match: "users.*", icon: Users, ability: "users.view" },
        { label: "Perfis", route: "roles.index", match: "roles.*", icon: ShieldCheck, ability: "roles.view" },
        { label: "Permissões", route: "permissions.index", match: "permissions.*", icon: KeyRound, ability: "permissions.view" },
    ].filter((tab) => can(tab.ability))
);

const breadcrumbItems = computed(() => [
    { label: "Início", href: route(isTenant.value ? "patients.index" : "dashboard"), icon: Home },
    { label: "Controle de Acesso", href: tabs.value[0] ? aclRoute(tabs.value[0].route) : null },
    ...props.breadcrumbs.map((item, index) => ({
        label: item.label,
        href: item.route && index < props.breadcrumbs.length - 1 ? aclRoute(item.route, item.params) : null,
    })),
]);

const isActive = (tab) => route().current(`${acl.value.routePrefix}${tab.match}`);

// Mensagens de retorno do backend (with('success') / with('error'))
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) showToast(flash.success, "success");
        if (flash?.error) showToast(flash.error, "error");
    },
    { immediate: true }
);
</script>

<template>
    <Head :title="title" />
    <component :is="layout" v-bind="layoutProps">
        <Breadcrumb :items="breadcrumbItems" />

        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">{{ title }}</h1>
                <p v-if="description" class="mt-1 text-sm text-gray-500">{{ description }}</p>
            </div>
            <div v-if="$slots.actions" class="flex flex-wrap gap-2">
                <slot name="actions" />
            </div>
        </div>

        <nav v-if="tabs.length > 1" class="mb-5 flex gap-1 overflow-x-auto border-b border-gray-200"
            aria-label="Seções do controle de acesso">
            <Link v-for="tab in tabs" :key="tab.route" :href="aclRoute(tab.route)"
                :aria-current="isActive(tab) ? 'page' : undefined"
                class="-mb-px inline-flex items-center gap-2 whitespace-nowrap border-b-2 px-4 py-2.5 text-sm font-medium transition-colors"
                :class="isActive(tab)
                    ? 'border-cyan-600 text-cyan-700'
                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'">
                <component :is="tab.icon" class="h-4 w-4" />
                {{ tab.label }}
            </Link>
        </nav>

        <slot />
    </component>
</template>
