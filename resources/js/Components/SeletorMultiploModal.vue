<script setup>
import { computed, nextTick, ref, watch } from "vue";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/Components/ui/dialog";
import { Check, ChevronDown, Search, X } from "lucide-vue-next";

/**
 * Campo que abre um modal para escolher vários itens: busca, "Selecionar todos",
 * "Limpar" e lista com caixas de seleção. A escolha só vale ao clicar em Confirmar
 * (que exige pelo menos um item); Cancelar descarta.
 */
const props = defineProps({
    id: { type: String, required: true },
    // [{ value, label, hint? }]
    items: { type: Array, default: () => [] },
    placeholder: { type: String, default: "Escolher" },
    titulo: { type: String, required: true },
    descricao: { type: String, default: "" },
    buscaPlaceholder: { type: String, default: "Buscar..." },
    vazio: { type: String, default: "Nenhum resultado encontrado." },
    // Texto do campo quando todos estão escolhidos (ex.: "Todos os planos").
    rotuloTodos: { type: String, default: "Todos" },
    error: { type: [String, Boolean], default: false },
});

const selecionados = defineModel({ type: Array, default: () => [] });

const aberto = ref(false);
const termo = ref("");
const marcados = ref([]);
const inputRef = ref(null);

const normalizar = (texto) => String(texto ?? "").normalize("NFD").replace(/[̀-ͯ]/g, "").toLowerCase();
const mesmo = (a, b) => String(a) === String(b);

const filtrados = computed(() => {
    const q = normalizar(termo.value);
    return props.items.filter((item) => !q || normalizar(`${item.label} ${item.hint ?? ""}`).includes(q));
});

const marcado = (item) => marcados.value.some((v) => mesmo(v, item.value));
const todosMarcados = computed(() => props.items.length > 0 && marcados.value.length === props.items.length);

const alternar = (item) => {
    marcados.value = marcado(item) ? marcados.value.filter((v) => !mesmo(v, item.value)) : [...marcados.value, item.value];
};

const marcarTodos = () => (marcados.value = props.items.map((item) => item.value));
const limpar = () => (marcados.value = []);

watch(aberto, async (abriu) => {
    if (!abriu) return;
    termo.value = "";
    marcados.value = [...selecionados.value];
    await nextTick();
    inputRef.value?.focus();
});

const confirmar = () => {
    if (!marcados.value.length) return;
    // Mantém a ordem da lista original.
    selecionados.value = props.items.filter((item) => marcado(item)).map((item) => item.value);
    aberto.value = false;
};

const escolhidos = computed(() => props.items.filter((item) => selecionados.value.some((v) => mesmo(v, item.value))));
const textoCampo = computed(() => {
    if (!escolhidos.value.length) return null;
    if (escolhidos.value.length === props.items.length) return props.rotuloTodos;
    return escolhidos.value.map((item) => item.label).join(", ");
});
</script>

<template>
    <div>
        <button :id="id" type="button" aria-haspopup="dialog" :aria-invalid="!!error" @click="aberto = true"
            class="flex min-h-11 w-full items-center justify-between gap-2 rounded-lg border bg-white px-3 text-left text-base focus:outline-none focus-visible:ring-2 sm:text-sm"
            :class="error ? 'border-red-500 focus-visible:ring-red-500' : 'border-gray-300 focus-visible:ring-cyan-500'">
            <span class="min-w-0 truncate" :class="textoCampo ? 'text-gray-900' : 'text-gray-500'">{{ textoCampo ?? placeholder }}</span>
            <span class="flex shrink-0 items-center gap-2">
                <span v-if="escolhidos.length && escolhidos.length < items.length"
                    class="rounded-full bg-cyan-100 px-2 py-0.5 text-xs font-semibold text-cyan-800 dark:bg-cyan-950/50 dark:text-cyan-300">
                    {{ escolhidos.length }} de {{ items.length }}
                </span>
                <ChevronDown class="h-4 w-4 text-gray-500" aria-hidden="true" />
            </span>
        </button>

        <Dialog v-model:open="aberto">
            <DialogContent class="gap-0 overflow-hidden p-0 sm:max-w-[480px]">
                <DialogHeader class="p-5 pb-3">
                    <DialogTitle>{{ titulo }}</DialogTitle>
                    <DialogDescription v-if="descricao">{{ descricao }}</DialogDescription>
                </DialogHeader>

                <div class="space-y-3 px-5 pb-3">
                    <div class="relative">
                        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" aria-hidden="true" />
                        <input ref="inputRef" v-model="termo" type="search" :placeholder="buscaPlaceholder" :aria-label="buscaPlaceholder" autocomplete="off"
                            class="min-h-11 w-full rounded-lg border border-gray-300 pl-9 pr-9 text-base focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 sm:text-sm" />
                        <button v-if="termo" type="button" aria-label="Limpar busca" @click="termo = ''; inputRef?.focus()"
                            class="absolute right-2 top-1/2 -translate-y-1/2 rounded p-1 text-gray-500 hover:text-gray-700">
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm text-gray-700" aria-live="polite"><strong>{{ marcados.length }}</strong> de {{ items.length }} selecionado(s)</p>
                        <div class="flex gap-1">
                            <button type="button" :disabled="todosMarcados" @click="marcarTodos"
                                class="inline-flex min-h-10 items-center rounded-lg px-3 text-sm font-semibold text-cyan-700 hover:bg-cyan-50 disabled:cursor-not-allowed disabled:opacity-40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500">
                                Selecionar todos
                            </button>
                            <button type="button" :disabled="!marcados.length" @click="limpar"
                                class="inline-flex min-h-10 items-center rounded-lg px-3 text-sm font-medium text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500">
                                Limpar
                            </button>
                        </div>
                    </div>
                </div>

                <div class="max-h-80 overflow-y-auto border-t border-gray-100" role="group" :aria-label="titulo">
                    <p v-if="!filtrados.length" class="py-10 text-center text-sm text-gray-600">{{ vazio }}</p>

                    <button v-for="item in filtrados" :key="item.value" type="button" role="checkbox" :aria-checked="marcado(item)"
                        @click="alternar(item)"
                        class="flex min-h-11 w-full items-center gap-3 px-5 py-2.5 text-left text-sm transition-colors duration-150 hover:bg-gray-50 focus-visible:bg-cyan-50 focus-visible:outline-none motion-reduce:transition-none">
                        <span class="grid h-5 w-5 shrink-0 place-items-center rounded border-2"
                            :class="marcado(item) ? 'border-cyan-600 bg-cyan-600 text-white' : 'border-gray-400 bg-white'" aria-hidden="true">
                            <Check v-if="marcado(item)" class="h-3.5 w-3.5" />
                        </span>
                        <span class="min-w-0 flex-1 truncate" :class="marcado(item) ? 'font-semibold text-gray-900' : 'text-gray-800'">{{ item.label }}</span>
                        <span v-if="item.hint" class="shrink-0 text-xs text-gray-600">{{ item.hint }}</span>
                    </button>
                </div>

                <DialogFooter class="flex-col-reverse gap-2 border-t border-gray-100 p-4 sm:flex-row sm:justify-end">
                    <button type="button" @click="aberto = false"
                        class="inline-flex min-h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500">
                        Cancelar
                    </button>
                    <button type="button" :disabled="!marcados.length" @click="confirmar"
                        class="inline-flex min-h-11 items-center justify-center rounded-lg bg-cyan-600 px-4 text-sm font-semibold text-white hover:bg-cyan-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                        Confirmar{{ marcados.length ? ` (${marcados.length})` : '' }}
                    </button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
