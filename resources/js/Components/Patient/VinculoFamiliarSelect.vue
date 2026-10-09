<script setup>
import { computed, ref } from "vue";
import SearchListModal from "@/Components/SearchListModal.vue";
import { ChevronDown } from "lucide-vue-next";

/**
 * Vínculo do familiar: abre um modal com busca e a lista em ordem alfabética;
 * a escolha vale ao clicar em Confirmar.
 */
const props = defineProps({
    // [{ value, label }] (TipoVinculoFamiliar::options)
    tipos: { type: Array, default: () => [] },
    id: { type: String, required: true },
    error: { type: String, default: null },
});

const tipo = defineModel({ type: String, default: "" });
const aberto = ref(false);

const itens = computed(() => [...props.tipos].sort((a, b) => a.label.localeCompare(b.label, "pt-BR", { sensitivity: "base" })));
const atual = computed(() => props.tipos.find((t) => t.value === tipo.value));
</script>

<template>
    <div>
        <button :id="id" type="button" aria-haspopup="dialog" :aria-invalid="!!error"
            class="flex h-10 w-full items-center justify-between gap-2 rounded-lg border bg-white px-3 text-left text-sm focus:outline-none focus:ring-2"
            :class="error ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 focus:ring-cyan-500'" @click="aberto = true">
            <span class="truncate" :class="atual ? 'text-gray-900' : 'text-gray-500'">{{ atual?.label ?? "Selecione" }}</span>
            <ChevronDown class="h-4 w-4 shrink-0 text-gray-400" aria-hidden="true" />
        </button>

        <SearchListModal v-model:open="aberto" title="Selecionar vínculo" description="Parentesco do familiar com o beneficiário."
            placeholder="Buscar vínculo..." empty-text="Nenhum vínculo encontrado." :items="itens" :selected="tipo" confirmar
            @select="(item) => (tipo = item.value)" />
    </div>
</template>
