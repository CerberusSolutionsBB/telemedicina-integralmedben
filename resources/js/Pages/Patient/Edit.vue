<script setup>
import { computed } from "vue";
import { Head } from "@inertiajs/vue3";
import TenantAdminLayout from "@/Layouts/TenantAdminLayout.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import PatientForm from "@/Components/Patient/PatientForm.vue";
import SairSemSalvarDialog from "@/Components/SairSemSalvarDialog.vue";
import { usePatientForm } from "@/Composables/Patient/usePatientForm";
import { Home } from "lucide-vue-next";

const props = defineProps({
    breadcrumbs: { type: Array, required: true },
    patient: { type: Object, required: true },
    planos: { type: Array, default: () => [] },
    vendedores: { type: Array, default: () => [] },
    planoAtual: { type: Object, default: null },
    familiares: { type: Array, default: () => [] },
    tiposFamiliares: { type: Array, default: () => [] },
    limites: { type: Object, default: () => ({}) },
});

const { form, salvar, saida: { aberto: confirmarSaida, ficar, sair } } = usePatientForm({ patient: props.patient, familiares: props.familiares });

// Mesmo padrão do Controle de Acesso: "Início" › seção › registro atual.
const breadcrumbItems = computed(() => [
    { label: "Início", href: route("patients.index"), icon: Home },
    ...props.breadcrumbs,
]);

const nome = computed(() => props.patient.nome || `beneficiário #${props.patient.id}`);
</script>

<template>
    <Head :title="`Editar ${nome}`" />

    <TenantAdminLayout>
        <Breadcrumb :items="breadcrumbItems" />

        <div class="mb-5">
            <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">Editar {{ nome }}</h1>
            <p v-if="patient.email" class="mt-1 text-sm text-gray-500">{{ patient.email }}</p>
        </div>

        <PatientForm :form="form" :planos="planos" :vendedores="vendedores" :plano-atual="planoAtual"
            :tipos-familiares="tiposFamiliares" :limites="limites"
            :cancelar-href="route('patients.index')" rotulo-salvar="Salvar alterações" @submit="salvar" />

        <SairSemSalvarDialog v-model:open="confirmarSaida" @ficar="ficar" @sair="sair" />
    </TenantAdminLayout>
</template>
