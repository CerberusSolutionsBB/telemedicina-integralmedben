<script setup>
import { computed, ref } from "vue";
import SearchListModal from "@/Components/SearchListModal.vue";
import { ChevronDown, X } from "lucide-vue-next";

/**
 * Vendedor / indicado por: abre um modal com os usuários em ordem alfabética,
 * busca por nome e filtro por perfil.
 */
const props = defineProps({
    // [{ id, name, perfis: [] }]
    vendedores: { type: Array, default: () => [] },
    error: { type: String, default: null },
});

const vendedor = defineModel({ type: [Number, null], default: null });
const aberto = ref(false);

const ordenar = (a, b) => a.localeCompare(b, "pt-BR", { sensitivity: "base" });

const itens = computed(() =>
    [...props.vendedores]
        .sort((a, b) => ordenar(a.name, b.name))
        .map((v) => ({ value: String(v.id), label: v.name, hint: v.perfis.join(", "), filtros: v.perfis })),
);
const perfis = computed(() => [...new Set(props.vendedores.flatMap((v) => v.perfis))].sort(ordenar));
const selecionado = computed(() => props.vendedores.find((v) => v.id === vendedor.value));

const escolher = (item) => {
    vendedor.value = Number(item.value);
};
</script>

<template>
    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700" for="vendedor">Vendedor / indicado por</label>
        <div class="relative">
            <button id="vendedor" type="button" aria-haspopup="dialog"
                class="flex h-10 w-full items-center justify-between gap-2 rounded-lg border bg-white px-3 text-left text-sm focus:outline-none focus:ring-2"
                :class="[error ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 focus:ring-cyan-500', selecionado ? 'pr-16' : '']"
                @click="aberto = true">
                <span class="truncate" :class="selecionado ? 'text-gray-900' : 'text-gray-500'">
                    {{ selecionado?.name ?? "Não informado" }}
                </span>
                <ChevronDown v-if="!selecionado" class="h-4 w-4 shrink-0 text-gray-400" aria-hidden="true" />
            </button>
            <button v-if="selecionado" type="button" :aria-label="`Remover ${selecionado.name}`" title="Remover vendedor"
                class="absolute right-2 top-1/2 -translate-y-1/2 rounded p-1 text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-cyan-500"
                @click="vendedor = null">
                <X class="h-4 w-4" />
            </button>
        </div>
        <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
        <p class="mt-1 text-xs text-gray-500">Usuário que recebe a comissão pela venda deste plano.</p>

        <SearchListModal v-model:open="aberto" title="Selecionar vendedor" description="Usuários em ordem alfabética."
            placeholder="Buscar pelo nome..." empty-text="Nenhum usuário encontrado." :items="itens" :filtros="perfis"
            :selected="vendedor ? String(vendedor) : ''" @select="escolher" />
    </div>
</template>
