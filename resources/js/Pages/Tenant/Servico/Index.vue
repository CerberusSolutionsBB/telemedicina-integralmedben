<script setup>
import CadastroIconeLista from "@/Components/CadastroIcone/CadastroIconeLista.vue";
import { SERVICOS } from "@/Composables/CadastroIcone/modulos";

defineProps({
    itens: { type: Array, default: () => [] },
});
</script>

<template>
    <CadastroIconeLista :modulo="SERVICOS" :itens="itens" />
</template>
