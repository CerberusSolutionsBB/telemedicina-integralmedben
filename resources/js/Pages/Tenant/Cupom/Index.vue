<script setup>
import CadastroIconeLista from "@/Components/CadastroIcone/CadastroIconeLista.vue";
import CupomBeneficioResumo from "@/Components/CupomBeneficio/CupomBeneficioResumo.vue";
import { CUPONS_BENEFICIO } from "@/Composables/CadastroIcone/modulos";

defineProps({
    itens: { type: Array, default: () => [] },
});
</script>

<template>
    <CadastroIconeLista :modulo="CUPONS_BENEFICIO" :itens="itens">
        <template #resumo="{ item }"><CupomBeneficioResumo :item="item" /></template>
    </CadastroIconeLista>
</template>
