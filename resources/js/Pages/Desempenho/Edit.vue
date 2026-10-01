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
    desempenho: { type: Object, required: true },
    roles: { type: Array, default: () => [] },
    funcoes: { type: Array, default: () => [] },
    tipos: { type: Array, default: () => [] },
    planos: { type: Array, default: () => [] },
    limites: { type: Object, required: true },
});

const { form, salvar } = useDesempenhoForm({ desempenho: props.desempenho });

const breadcrumbItems = computed(() => [
    { label: "Início", href: route("patients.index"), icon: Home },
    ...props.breadcrumbs,
]);
</script>

<template>
    <Head :title="`Editar ${desempenho.titulo}`" />

    <TenantAdminLayout>
        <Breadcrumb :items="breadcrumbItems" />

        <div class="mb-5">
            <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">Editar {{ desempenho.titulo }}</h1>
            <p class="mt-1 text-sm text-gray-500">Alterações valem para o cálculo do progresso imediatamente.</p>
        </div>

        <DesempenhoForm :form="form" :roles="roles" :funcoes="funcoes" :tipos="tipos" :planos="planos" :limites="limites"
            :cancelar-href="route('desempenho.show', desempenho.id)" rotulo-salvar="Salvar alterações" @submit="salvar" />
    </TenantAdminLayout>
</template>
