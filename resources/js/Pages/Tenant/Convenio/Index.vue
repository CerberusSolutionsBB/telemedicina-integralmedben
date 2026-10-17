<script setup>
import CadastroIconeLista from "@/Components/CadastroIcone/CadastroIconeLista.vue";
import { CONVENIOS } from "@/Composables/CadastroIcone/modulos";

defineProps({
    itens: { type: Array, default: () => [] },
});
</script>

<template>
    <CadastroIconeLista :modulo="CONVENIOS" :itens="itens" />
</template>
