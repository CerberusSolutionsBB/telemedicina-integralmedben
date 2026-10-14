<script setup>
import { computed } from "vue";
import { Head } from "@inertiajs/vue3";
import CentralAdminLayout from "@/Layouts/CentralAdminLayout.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import CupomDetalhes from "@/Components/Cupom/CupomDetalhes.vue";
import { Home } from "lucide-vue-next";

const props = defineProps({
    breadcrumbs: { type: Array, required: true },
    cupom: { type: Object, required: true },
    usos: { type: Array, default: () => [] },
});

const rotas = { edit: "financeiro.cupons.edit", pdf: "financeiro.cupons.pdf", status: "financeiro.cupons.status", destroy: "financeiro.cupons.destroy" };

const breadcrumbItems = computed(() => [
    { label: "Início", href: route("dashboard"), icon: Home },
    ...props.breadcrumbs,
]);
</script>

<template>
    <Head :title="`Cupom ${cupom.codigo}`" />

    <CentralAdminLayout>
        <Breadcrumb :items="breadcrumbItems" />

        <CupomDetalhes :cupom="cupom" :usos="usos" :rotas="rotas" mostrar-parceiro />
    </CentralAdminLayout>
</template>
