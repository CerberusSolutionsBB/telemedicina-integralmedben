<script setup>
import { computed } from "vue";
import { Head } from "@inertiajs/vue3";
import TenantAdminLayout from "@/Layouts/TenantAdminLayout.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import DesempenhoForm from "@/Components/Desempenho/DesempenhoForm.vue";
import { useDesempenhoForm } from "@/Composables/Desempenho/useDesempenhoForm";
import { Home } from "lucide-vue-next";

const props = defineProps({
    breadcrumbs: { type: Array, required: true },
    roles: { type: Array, default: () => [] },
    funcoes: { type: Array, default: () => [] },
    tipos: { type: Array, default: () => [] },
    planos: { type: Array, default: () => [] },
    limites: { type: Object, required: true },
});

const { form, salvar } = useDesempenhoForm();

const breadcrumbItems = computed(() => [
    { label: "Início", href: route("patients.index"), icon: Home },
    ...props.breadcrumbs,
]);
</script>

<template>
    <Head title="Nova meta" />

    <TenantAdminLayout>
        <Breadcrumb :items="breadcrumbItems" />

        <div class="mb-5">
            <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">Nova meta</h1>
            <p class="mt-1 text-sm text-gray-500">Defina quem participa, o que conta e até quando a meta deve ser batida.</p>
        </div>

        <DesempenhoForm :form="form" :roles="roles" :funcoes="funcoes" :tipos="tipos" :planos="planos" :limites="limites"
            :cancelar-href="route('desempenho.index')" rotulo-salvar="Criar meta" @submit="salvar" />
    </TenantAdminLayout>
</template>
