<script setup>
import { computed } from "vue";
import { Head } from "@inertiajs/vue3";
import CentralAdminLayout from "@/Layouts/CentralAdminLayout.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import CupomForm from "@/Components/Cupom/CupomForm.vue";
import { useCupomForm } from "@/Composables/Cupom/useCupomForm";
import { Home } from "lucide-vue-next";

const props = defineProps({
    breadcrumbs: { type: Array, required: true },
    cupom: { type: Object, required: true },
    parceiros: { type: Array, default: () => [] },
    opcoes: { type: Object, required: true },
});

const rotas = { store: "financeiro.cupons.store", update: "financeiro.cupons.update" };
const { form, salvar } = useCupomForm({ cupom: props.cupom, rotas });

const breadcrumbItems = computed(() => [
    { label: "Início", href: route("dashboard"), icon: Home },
    ...props.breadcrumbs,
]);
</script>

<template>
    <Head title="Cupons" />

    <CentralAdminLayout>
        <Breadcrumb :items="breadcrumbItems" />

        <div class="mb-5">
            <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">Editar cupom {{ cupom.codigo }}</h1>
            <p class="mt-1 text-sm text-gray-500">Situação, PDF e histórico de usos ficam na página do cupom.</p>
        </div>

        <CupomForm :form="form" :opcoes="opcoes" :parceiros="parceiros" :usado="cupom.usos > 0"
            :cancelar-href="route('financeiro.cupons.show', cupom.id)" rotulo-salvar="Salvar alterações" @submit="salvar" />
    </CentralAdminLayout>
</template>
