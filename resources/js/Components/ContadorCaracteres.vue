<script setup>
import { computed } from "vue";

/**
 * Contador "n/limite" abaixo de um campo de texto: âmbar a partir de 90% e vermelho no limite.
 */
const props = defineProps({
    valor: { type: [String, Number], default: "" },
    limite: { type: Number, required: true },
});

const usados = computed(() => String(props.valor ?? "").length);
const classe = computed(() =>
    usados.value >= props.limite ? "text-red-600" : usados.value >= props.limite * 0.9 ? "text-amber-700" : "text-gray-500",
);
</script>

<template>
    <span class="ml-auto shrink-0 text-xs tabular-nums" :class="classe" aria-live="polite">
        {{ usados }}/{{ limite }}
    </span>
</template>
