<script setup>
import { computed, ref } from "vue";
import SearchListModal from "@/Components/SearchListModal.vue";
import { ChevronDown, X } from "lucide-vue-next";

/**
 * Campo que parece um select e abre um modal com busca e lista (SearchListModal):
 * clicar num item só o marca, e a escolha vale ao clicar em Confirmar.
 * Aceita valores numéricos (ids): devolve o valor no tipo original do item.
 */
const props = defineProps({
    id: { type: String, required: true },
    // [{ value, label, hint? }]
    items: { type: Array, default: () => [] },
    placeholder: { type: String, default: "Selecione" },
    titulo: { type: String, required: true },
    descricao: { type: String, default: "" },
    buscaPlaceholder: { type: String, default: "Buscar..." },
    vazio: { type: String, default: "Nenhum resultado encontrado." },
    // Mostra o botão de limpar a escolha (campo opcional).
    limpavel: { type: Boolean, default: false },
    error: { type: [String, Boolean], default: false },
});

const valor = defineModel({ type: [String, Number], default: "" });
const aberto = ref(false);

// O modal compara strings; o v-model mantém o tipo do item (ex.: id numérico).
const itens = computed(() =>
    [...props.items]
        .sort((a, b) => a.label.localeCompare(b.label, "pt-BR", { sensitivity: "base" }))
        .map((item) => ({ ...item, value: String(item.value), original: item.value })),
);
const atual = computed(() => itens.value.find((item) => item.value === String(valor.value ?? "")));
</script>

<template>
    <div class="relative">
        <button :id="id" type="button" aria-haspopup="dialog" :aria-invalid="!!error" @click="aberto = true"
            class="flex min-h-11 w-full items-center justify-between gap-2 rounded-lg border bg-white px-3 text-left text-base focus:outline-none focus:ring-2 sm:text-sm"
            :class="error ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 focus:ring-cyan-500'">
            <span class="truncate" :class="atual ? 'text-gray-900' : 'text-gray-500'">
                {{ atual?.label ?? placeholder }}
                <span v-if="atual?.hint" class="text-xs text-gray-600">· {{ atual.hint }}</span>
            </span>
            <span class="flex shrink-0 items-center gap-1">
                <!-- Espaço do botão de limpar, que fica por cima (fora do botão principal) -->
                <span v-if="limpavel && atual" class="w-6" aria-hidden="true" />
                <ChevronDown class="h-4 w-4 text-gray-400" aria-hidden="true" />
            </span>
        </button>

        <button v-if="limpavel && atual" type="button" :aria-label="`Limpar ${titulo.toLowerCase()}`" title="Limpar"
            class="absolute right-8 top-1/2 -translate-y-1/2 rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
            @click="valor = ''">
            <X class="h-4 w-4" />
        </button>

        <SearchListModal v-model:open="aberto" :title="titulo" :description="descricao" :placeholder="buscaPlaceholder"
            :empty-text="vazio" :items="itens" :selected="atual?.value ?? ''" confirmar
            @select="(item) => (valor = item.original)" />
    </div>
</template>
