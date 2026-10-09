<script setup>
import { computed } from "vue";
import { Head } from "@inertiajs/vue3";
import TenantAdminLayout from "@/Layouts/TenantAdminLayout.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import PatientForm from "@/Components/Patient/PatientForm.vue";
import { usePatientForm } from "@/Composables/Patient/usePatientForm";
import { Home } from "lucide-vue-next";

const props = defineProps({
    breadcrumbs: { type: Array, required: true },
    tenantName: { type: String, default: "" },
    tenantPhoto: { type: String, default: null },
    planos: { type: Array, default: () => [] },
    vendedores: { type: Array, default: () => [] },
    tiposFamiliares: { type: Array, default: () => [] },
    limites: { type: Object, default: () => ({}) },
});

const { form, salvar } = usePatientForm();

// Mesmo padrão do Controle de Acesso: "Início" › seção › tela atual.
const breadcrumbItems = computed(() => [
    { label: "Início", href: route("patients.index"), icon: Home },
    ...props.breadcrumbs,
]);
</script>

<template>
    <Head title="Novo beneficiário" />

    <TenantAdminLayout :tenant-name="tenantName" :tenant-photo="tenantPhoto">
        <Breadcrumb :items="breadcrumbItems" />

        <div class="mb-5">
            <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">Novo beneficiário</h1>
            <p class="mt-1 text-sm text-gray-500">Cadastre o beneficiário e vincule um plano de telemedicina.</p>
        </div>

        <PatientForm :form="form" :planos="planos" :vendedores="vendedores" :tipos-familiares="tiposFamiliares" :limites="limites" :cancelar-href="route('patients.index')"
            rotulo-salvar="Cadastrar beneficiário" @submit="salvar" />
    </TenantAdminLayout>
</template>
