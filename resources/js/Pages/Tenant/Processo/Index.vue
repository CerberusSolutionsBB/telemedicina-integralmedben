<script setup>
import CadastroIconeLista from "@/Components/CadastroIcone/CadastroIconeLista.vue";
import { PROCESSOS } from "@/Composables/CadastroIcone/modulos";

defineProps({
    itens: { type: Array, default: () => [] },
});
</script>

<template>
    <CadastroIconeLista :modulo="PROCESSOS" :itens="itens" />
</template>
