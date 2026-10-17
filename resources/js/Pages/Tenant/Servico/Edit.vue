<script setup>
import CadastroIconeEdicao from "@/Components/CadastroIcone/CadastroIconeEdicao.vue";
import { SERVICOS } from "@/Composables/CadastroIcone/modulos";

defineProps({
    breadcrumbs: { type: Array, required: true },
    item: { type: Object, required: true },
    icones: { type: Array, default: () => [] },
    limites: { type: Object, required: true },
});
</script>

<template>
    <CadastroIconeEdicao :modulo="SERVICOS" :breadcrumbs="breadcrumbs" :item="item" :icones="icones" :limites="limites" />
</template>
