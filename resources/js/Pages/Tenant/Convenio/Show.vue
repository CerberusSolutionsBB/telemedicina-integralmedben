<script setup>
import CadastroIconeDetalhe from "@/Components/CadastroIcone/CadastroIconeDetalhe.vue";
import { CONVENIOS } from "@/Composables/CadastroIcone/modulos";

defineProps({
    breadcrumbs: { type: Array, required: true },
    item: { type: Object, required: true },
    historico: { type: Array, default: () => [] },
});
</script>

<template>
    <CadastroIconeDetalhe :modulo="CONVENIOS" :breadcrumbs="breadcrumbs" :item="item" :historico="historico" />
</template>
