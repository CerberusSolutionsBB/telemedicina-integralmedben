<script setup>
import { computed } from "vue";
import { Head } from "@inertiajs/vue3";
import TenantAdminLayout from "@/Layouts/TenantAdminLayout.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import CupomForm from "@/Components/Cupom/CupomForm.vue";

import { useCupomForm } from "@/Composables/Cupom/useCupomForm";
import { Home } from "lucide-vue-next";

const props = defineProps({
    breadcrumbs: { type: Array, required: true },
    teto: { type: Number, default: null },
    opcoes: { type: Object, required: true },
});

const rotas = { store: "cupons.store", update: "cupons.update" };
const { form, salvar } = useCupomForm({ rotas });

const breadcrumbItems = computed(() => [
    { label: "Início", href: route("patients.index"), icon: Home },
    ...props.breadcrumbs,
]);
</script>

<template>
    <Head title="Cupons" />

    <TenantAdminLayout>
        <Breadcrumb :items="breadcrumbItems" />

        <div class="mb-5">
            <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">Novo cupom</h1>
            <p class="mt-1 text-sm text-gray-500">Defina o desconto, a validade e onde o cupom vale.</p>
        </div>

        <CupomForm :form="form" :opcoes="opcoes" :teto="teto"
            :cancelar-href="route('cupons.index')" rotulo-salvar="Criar cupom" @submit="salvar" />


    </TenantAdminLayout>
</template>
