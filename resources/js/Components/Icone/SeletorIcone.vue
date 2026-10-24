<script setup>
import { computed, ref } from "vue";
import IconeLucide from "@/Components/Icone/IconeLucide.vue";
import SeletorIconeModal from "@/Components/Icone/SeletorIconeModal.vue";
import { rotuloIcone } from "@/Composables/Icone/useIconesLucide";
import { AlertCircle, ImagePlus, RefreshCw } from "lucide-vue-next";

/**
 * Campo de ícone: mostra o escolhido e abre o modal com a grade e a busca
 * (SeletorIconeModal) para escolher ou trocar.
 */
const props = defineProps({
    modelValue: { type: String, default: "" },
    // [{ value, label }] sugeridos, com rótulo em português
    sugeridos: { type: Array, default: () => [] },
    error: { type: String, default: "" },
});

const emit = defineEmits(["update:modelValue"]);

const aberto = ref(false);

const selecionadoLabel = computed(() => (props.modelValue ? rotuloIcone(props.modelValue, props.sugeridos) : null));
</script>

<template>
    <div>
        <div class="flex flex-col gap-3 rounded-lg border p-3 sm:flex-row sm:items-center sm:justify-between"
            :class="error ? 'border-red-500' : 'border-gray-200'">
            <div class="flex items-center gap-3">
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl"
                    :class="modelValue ? 'bg-cyan-50 text-cyan-700' : 'border border-dashed border-gray-300 text-gray-400'">
                    <IconeLucide v-if="modelValue" :nome="modelValue" class="h-6 w-6" />
                    <ImagePlus v-else class="h-6 w-6" aria-hidden="true" />
                </span>
                <p class="text-sm" :class="modelValue ? 'text-gray-800' : 'text-gray-600'">
                    <template v-if="modelValue">Selecionado: <strong>{{ selecionadoLabel }}</strong></template>
                    <template v-else>Nenhum ícone escolhido.</template>
                </p>
            </div>

            <button id="icone" type="button" aria-haspopup="dialog" :aria-invalid="!!error" aria-describedby="icone-erro"
                class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-cyan-600 bg-white px-4 text-sm font-semibold text-cyan-700 hover:bg-cyan-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 focus-visible:ring-offset-2"
                @click="aberto = true">
                <component :is="modelValue ? RefreshCw : ImagePlus" class="h-4 w-4" aria-hidden="true" />
                {{ modelValue ? 'Trocar ícone' : 'Escolher ícone' }}
            </button>
        </div>

        <p v-if="error" id="icone-erro" class="mt-1 flex items-start gap-1 text-sm text-red-600 dark:text-red-400">
            <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />{{ error }}
        </p>

        <SeletorIconeModal v-model:open="aberto" :selecionado="modelValue" :sugeridos="sugeridos"
            descricao="Procure pelo nome em português ou inglês. Clique para marcar e confirme."
            @select="(nome) => emit('update:modelValue', nome)" />
    </div>
</template>
