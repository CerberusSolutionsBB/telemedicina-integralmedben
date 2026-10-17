<script setup>
import { ref } from "vue";
import { showToast } from "@/Utils/toast";
import { Check, Copy } from "lucide-vue-next";

/**
 * Código do cupom em destaque, com botão de copiar (para colar numa mensagem ao beneficiário).
 */
const props = defineProps({
    codigo: { type: String, required: true },
});

const copiado = ref(false);

const copiar = async () => {
    try {
        await navigator.clipboard.writeText(props.codigo);
        copiado.value = true;
        showToast(`Código ${props.codigo} copiado.`, "success");
        setTimeout(() => (copiado.value = false), 2000);
    } catch {
        showToast("Não foi possível copiar. Selecione o código e copie manualmente.", "warning");
    }
};
</script>

<template>
    <section class="w-full rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
        <h2 class="text-base font-semibold text-gray-900">Código do cupom</h2>
        <p class="mt-0.5 text-sm text-gray-600">O beneficiário apresenta este código no convênio ou serviço.</p>
        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
            <p class="select-all rounded-lg border-2 border-dashed border-cyan-500 bg-cyan-50 px-5 py-3 text-center font-mono text-2xl font-bold tracking-[0.2em] text-gray-900 dark:border-cyan-700">
                {{ codigo }}
            </p>
            <button type="button" @click="copiar"
                class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 focus-visible:ring-offset-2">
                <component :is="copiado ? Check : Copy" class="h-4 w-4" aria-hidden="true" />
                {{ copiado ? 'Copiado' : 'Copiar código' }}
            </button>
        </div>
    </section>
</template>
