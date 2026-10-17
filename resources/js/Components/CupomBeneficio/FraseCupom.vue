<script setup>
import { computed } from "vue";
import { fraseCupom } from "@/Composables/CupomBeneficio/fraseCupom";
import { Info, Sparkles } from "lucide-vue-next";

/**
 * Frase em linguagem simples com o que o cupom dá (no formulário, ao vivo; e no detalhe).
 */
const props = defineProps({
    tipo: { type: String, default: "percentual" },
    valor: { type: [String, Number], default: "" },
    plano: { type: String, default: null },
    convenio: { type: String, default: null },
    servico: { type: String, default: null },
    prazo: { type: String, default: null },
    codigo: { type: String, default: null },
    titulo: { type: String, default: "Como o cupom fica" },
});

const frase = computed(() => fraseCupom(props));
</script>

<template>
    <section class="w-full rounded-xl border p-4 sm:p-6" aria-live="polite"
        :class="frase.completa ? 'border-cyan-200 bg-cyan-50 dark:border-cyan-900' : 'border-dashed border-gray-300 bg-white'">
        <h2 class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wide"
            :class="frase.completa ? 'text-cyan-800' : 'text-gray-600'">
            <component :is="frase.completa ? Sparkles : Info" class="h-4 w-4" aria-hidden="true" /> {{ titulo }}
        </h2>
        <p class="mt-2 text-base leading-relaxed" :class="frase.completa ? 'font-medium text-gray-900' : 'text-gray-600'">{{ frase.texto }}</p>
    </section>
</template>
