<script setup>
import { computed, nextTick, ref, watch } from "vue";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/Components/ui/dialog";
import { Check, Loader2, Search, X } from "lucide-vue-next";
import { normalizar } from "@/Composables/useLocalidades";

const props = defineProps({
    title: { type: String, required: true },
    description: { type: String, default: "" },
    // [{ value, label, hint? }]
    items: { type: Array, default: () => [] },
    selected: { type: String, default: "" },
    loading: { type: Boolean, default: false },
    placeholder: { type: String, default: "Buscar..." },
    emptyText: { type: String, default: "Nenhum resultado encontrado." },
    // Filtro opcional em chips (ex.: perfis). Cada item informa os seus em item.filtros.
    filtros: { type: Array, default: () => [] },
    // Com confirmar, clicar só marca o item; a escolha vale ao clicar em Confirmar.
    confirmar: { type: Boolean, default: false },
});

const open = defineModel("open", { type: Boolean, default: false });
const emit = defineEmits(["select"]);

const termo = ref("");
const filtroAtivo = ref("");
const pendente = ref(null);
const destaque = ref(0);
const inputRef = ref(null);
const listaRef = ref(null);

const indexados = computed(() =>
    props.items.map((item) => ({ ...item, busca: normalizar(`${item.label} ${item.hint ?? ""}`) })),
);

const filtrados = computed(() => {
    const q = normalizar(termo.value);
    return indexados.value.filter((item) =>
        (!q || item.busca.includes(q)) && (!filtroAtivo.value || item.filtros?.includes(filtroAtivo.value)),
    );
});

const isSelecionado = (item) => normalizar(item.value) === normalizar(props.selected);
// No modo confirmar, o item marcado (ou o atual, enquanto nada foi marcado).
const isMarcado = (item) => (props.confirmar && pendente.value ? item.value === pendente.value.value : isSelecionado(item));

watch(open, async (aberto) => {
    if (!aberto) return;
    termo.value = "";
    filtroAtivo.value = "";
    pendente.value = null;
    await nextTick();
    const indice = filtrados.value.findIndex(isSelecionado);
    destaque.value = Math.max(0, indice);
    rolarAteDestaque();
    inputRef.value?.focus();
});

watch([termo, filtroAtivo], () => {
    destaque.value = 0;
});

const rolarAteDestaque = () => {
    nextTick(() => {
        listaRef.value?.querySelector(`[data-index="${destaque.value}"]`)?.scrollIntoView({ block: "nearest" });
    });
};

const mover = (passo) => {
    if (!filtrados.value.length) return;
    destaque.value = (destaque.value + passo + filtrados.value.length) % filtrados.value.length;
    rolarAteDestaque();
};

const escolher = (item) => {
    if (!item) return;
    if (props.confirmar) {
        pendente.value = item;
        return;
    }
    emit("select", item);
    open.value = false;
};

const confirmarEscolha = () => {
    if (!pendente.value) return;
    emit("select", pendente.value);
    open.value = false;
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-[480px] p-0 gap-0 overflow-hidden">
            <DialogHeader class="p-5 pb-3">
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription v-if="description">{{ description }}</DialogDescription>
            </DialogHeader>

            <div class="px-5 pb-3">
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input ref="inputRef" v-model="termo" type="text" :placeholder="placeholder" autocomplete="off"
                        class="w-full pl-9 pr-9 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-500"
                        @keydown.down.prevent="mover(1)" @keydown.up.prevent="mover(-1)"
                        @keydown.enter.prevent="escolher(filtrados[destaque])" />
                    <button v-if="termo" type="button" title="Limpar busca"
                        class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-gray-600"
                        @click="termo = ''; inputRef?.focus()">
                        <X class="w-4 h-4" />
                    </button>
                </div>
                <div v-if="filtros.length" class="mt-3 flex flex-wrap gap-2" role="group" aria-label="Filtrar">
                    <button v-for="filtro in ['', ...filtros]" :key="filtro || 'todos'" type="button"
                        :aria-pressed="filtroAtivo === filtro"
                        class="rounded-full border px-3 py-1 text-xs font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500"
                        :class="filtroAtivo === filtro ? 'border-cyan-500 bg-cyan-50 text-cyan-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'"
                        @click="filtroAtivo = filtro">
                        {{ filtro || "Todos" }}
                    </button>
                </div>
                <p v-if="!loading" class="mt-2 text-xs text-gray-500">{{ filtrados.length }} de {{ items.length }}</p>
            </div>

            <div ref="listaRef" class="max-h-80 overflow-y-auto border-t border-gray-100" role="listbox">
                <div v-if="loading" class="flex items-center justify-center gap-2 py-10 text-sm text-gray-500">
                    <Loader2 class="w-4 h-4 animate-spin" />
                    Carregando...
                </div>

                <p v-else-if="!filtrados.length" class="py-10 text-center text-sm text-gray-500">{{ emptyText }}</p>

                <button v-for="(item, index) in filtrados" v-else :key="item.value" type="button" role="option"
                    :data-index="index" :aria-selected="isMarcado(item)"
                    class="w-full flex items-center justify-between gap-3 px-5 py-2.5 text-left text-sm transition-colors"
                    :class="index === destaque ? 'bg-cyan-50' : 'hover:bg-gray-50'" @mouseenter="destaque = index"
                    @click="escolher(item)">
                    <span class="min-w-0 truncate" :class="isMarcado(item) ? 'font-semibold text-cyan-700' : 'text-gray-800'">
                        {{ item.label }}
                    </span>
                    <span class="flex items-center gap-2 shrink-0">
                        <span v-if="item.hint" class="text-xs text-gray-400">{{ item.hint }}</span>
                        <Check v-if="isMarcado(item)" class="w-4 h-4 text-cyan-600" />
                    </span>
                </button>
            </div>

            <DialogFooter v-if="confirmar" class="flex-col-reverse gap-2 border-t border-gray-100 p-4 sm:flex-row sm:justify-end">
                <button type="button"
                    class="inline-flex min-h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500"
                    @click="open = false">
                    Cancelar
                </button>
                <button type="button" :disabled="!pendente"
                    class="inline-flex min-h-10 items-center justify-center rounded-lg bg-cyan-600 px-4 text-sm font-semibold text-white hover:bg-cyan-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    @click="confirmarEscolha">
                    Confirmar
                </button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
