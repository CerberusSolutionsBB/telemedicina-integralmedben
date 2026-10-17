<script setup>
import { computed, watchEffect } from "vue";
import { ICONES_SUGERIDOS } from "@/Composables/Icone/sugeridos";
import { carregarIconesLucide, iconesLucide } from "@/Composables/Icone/useIconesLucide";
import { Briefcase } from "lucide-vue-next";

/**
 * Ícone lucide pelo nome gravado (serviços, convênios). Sugeridos renderizam na hora; os demais
 * esperam o mapa completo do lucide. Nome desconhecido cai num ícone genérico.
 */
const props = defineProps({
    nome: { type: String, default: "" },
});

const sugerido = computed(() => ICONES_SUGERIDOS[props.nome]);

watchEffect(() => {
    if (props.nome && !sugerido.value) {
        carregarIconesLucide();
    }
});

const componente = computed(() => sugerido.value ?? iconesLucide.value?.[props.nome] ?? Briefcase);
</script>

<template>
    <component :is="componente" aria-hidden="true" />
</template>
