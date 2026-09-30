<script setup>
import { computed, nextTick, ref, watch } from "vue";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/Components/ui/dialog";
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
});

const open = defineModel("open", { type: Boolean, default: false });
const emit = defineEmits(["select"]);

const termo = ref("");
const destaque = ref(0);
const inputRef = ref(null);
const listaRef = ref(null);

const indexados = computed(() =>
    props.items.map((item) => ({ ...item, busca: normalizar(`${item.label} ${item.hint ?? ""}`) })),
);

const filtrados = computed(() => {
    const q = normalizar(termo.value);
    return q ? indexados.value.filter((item) => item.busca.includes(q)) : indexados.value;
});

const isSelecionado = (item) => normalizar(item.value) === normalizar(props.selected);

watch(open, async (aberto) => {
    if (!aberto) return;
    termo.value = "";
    await nextTick();
    const indice = filtrados.value.findIndex(isSelecionado);
    destaque.value = Math.max(0, indice);
    rolarAteDestaque();
    inputRef.value?.focus();
});

watch(termo, () => {
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
    emit("select", item);
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
                <p v-if="!loading" class="mt-2 text-xs text-gray-500">{{ filtrados.length }} de {{ items.length }}</p>
            </div>

            <div ref="listaRef" class="max-h-80 overflow-y-auto border-t border-gray-100" role="listbox">
                <div v-if="loading" class="flex items-center justify-center gap-2 py-10 text-sm text-gray-500">
                    <Loader2 class="w-4 h-4 animate-spin" />
                    Carregando...
                </div>

                <p v-else-if="!filtrados.length" class="py-10 text-center text-sm text-gray-500">{{ emptyText }}</p>

                <button v-for="(item, index) in filtrados" v-else :key="item.value" type="button" role="option"
                    :data-index="index" :aria-selected="isSelecionado(item)"
                    class="w-full flex items-center justify-between gap-3 px-5 py-2.5 text-left text-sm transition-colors"
                    :class="index === destaque ? 'bg-cyan-50' : 'hover:bg-gray-50'" @mouseenter="destaque = index"
                    @click="escolher(item)">
                    <span class="min-w-0 truncate" :class="isSelecionado(item) ? 'font-semibold text-cyan-700' : 'text-gray-800'">
                        {{ item.label }}
                    </span>
                    <span class="flex items-center gap-2 shrink-0">
                        <span v-if="item.hint" class="text-xs text-gray-400">{{ item.hint }}</span>
                        <Check v-if="isSelecionado(item)" class="w-4 h-4 text-cyan-600" />
                    </span>
                </button>
            </div>
        </DialogContent>
    </Dialog>
</template>
