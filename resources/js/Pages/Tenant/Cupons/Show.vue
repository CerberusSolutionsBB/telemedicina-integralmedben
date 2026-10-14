<script setup>
import { computed } from "vue";
import { Head } from "@inertiajs/vue3";
import TenantAdminLayout from "@/Layouts/TenantAdminLayout.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import CupomDetalhes from "@/Components/Cupom/CupomDetalhes.vue";
import { Home } from "lucide-vue-next";

const props = defineProps({
    breadcrumbs: { type: Array, required: true },
    cupom: { type: Object, required: true },
    usos: { type: Array, default: () => [] },
});

const rotas = { edit: "cupons.edit", pdf: "cupons.pdf", status: "cupons.status", destroy: "cupons.destroy" };

const breadcrumbItems = computed(() => [
    { label: "Início", href: route("patients.index"), icon: Home },
    ...props.breadcrumbs,
]);
</script>

<template>
    <Head :title="`Cupom ${cupom.codigo}`" />

    <TenantAdminLayout>
        <Breadcrumb :items="breadcrumbItems" />

        <CupomDetalhes :cupom="cupom" :usos="usos" :rotas="rotas" />
    </TenantAdminLayout>
</template>
