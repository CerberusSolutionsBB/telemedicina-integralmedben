<script setup>
import { computed } from "vue";
import { Head } from "@inertiajs/vue3";
import TenantAdminLayout from "@/Layouts/TenantAdminLayout.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import CadastroIconeForm from "@/Components/CadastroIcone/CadastroIconeForm.vue";
import { useCadastroIconeForm } from "@/Composables/CadastroIcone/useCadastroIconeForm";
import { rotaDe } from "@/Composables/CadastroIcone/modulos";
import { Home } from "lucide-vue-next";

/** Tela de criação (sem `item`) ou edição de um cadastro com ícone. */
const props = defineProps({
    modulo: { type: Object, required: true },
    breadcrumbs: { type: Array, required: true },
    item: { type: Object, default: null },
    icones: { type: Array, default: () => [] },
    limites: { type: Object, required: true },
});

const { form, salvar } = useCadastroIconeForm(props.modulo, { item: props.item });

const breadcrumbItems = computed(() => [
    { label: "Início", href: route("patients.index"), icon: Home },
    ...props.breadcrumbs,
]);

const cancelar = computed(() => (props.item ? route(rotaDe(props.modulo, "show"), props.item.id) : route(rotaDe(props.modulo, "index"))));
</script>

<template>
    <Head :title="modulo.titulo" />

    <TenantAdminLayout>
        <Breadcrumb :items="breadcrumbItems" />

        <div class="mb-5">
            <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">{{ item ? `Editar ${item.titulo}` : modulo.novo }}</h1>
            <p class="mt-1 text-sm text-gray-500">
                {{ item ? `As alterações ficam registradas no histórico do ${modulo.singular}.` : `Cadastre um ${modulo.singular} com título, descrição, ícone e status.` }}
            </p>
        </div>

        <CadastroIconeForm :modulo="modulo" :form="form" :icones="icones" :limites="limites" :cancelar-href="cancelar"
            :rotulo-salvar="item ? 'Salvar alterações' : `Criar ${modulo.singular}`" @submit="salvar">
            <template v-if="$slots.campos" #campos="escopo"><slot name="campos" v-bind="escopo" /></template>
            <template v-if="$slots.status" #status="escopo"><slot name="status" v-bind="escopo" /></template>
            <template v-if="$slots.resumo" #resumo="escopo"><slot name="resumo" v-bind="escopo" /></template>
        </CadastroIconeForm>
    </TenantAdminLayout>
</template>
