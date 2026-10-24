<script setup>
import { computed, ref, watch } from "vue";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/Components/ui/dialog";
import GradeIcones from "@/Components/Icone/GradeIcones.vue";
import IconeLucide from "@/Components/Icone/IconeLucide.vue";
import { rotuloIcone } from "@/Composables/Icone/useIconesLucide";

/**
 * Modal de escolha do ícone: clicar num ícone só o marca, e a escolha vale ao
 * clicar em Confirmar (ou com duplo clique). Cancelar mantém o ícone atual.
 */
const props = defineProps({
    // Ícone atual (o modal abre com ele marcado).
    selecionado: { type: String, default: "" },
    sugeridos: { type: Array, default: () => [] },
    titulo: { type: String, default: "Escolher ícone" },
    descricao: { type: String, default: "" },
});

const open = defineModel("open", { type: Boolean, default: false });
const emit = defineEmits(["select"]);

const marcado = ref("");
const grade = ref(null);

watch(open, (aberto) => aberto && (marcado.value = props.selecionado));

const marcadoLabel = computed(() => (marcado.value ? rotuloIcone(marcado.value, props.sugeridos) : null));

const confirmar = (nome = marcado.value) => {
    if (!nome) return;
    emit("select", nome);
    open.value = false;
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="flex max-h-[90vh] flex-col gap-0 overflow-hidden p-0 sm:max-w-4xl"
            @open-auto-focus.prevent="grade?.focar()">
            <DialogHeader class="p-5 pb-3">
                <DialogTitle>{{ titulo }}</DialogTitle>
                <DialogDescription v-if="descricao">{{ descricao }}</DialogDescription>
            </DialogHeader>

            <div class="min-h-0 flex-1 overflow-y-auto px-5 pb-5">
                <GradeIcones ref="grade" v-model="marcado" :sugeridos="sugeridos" @escolher="confirmar" />
            </div>

            <DialogFooter class="flex-col gap-3 border-t border-gray-100 p-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="flex items-center gap-2 text-sm text-gray-700" aria-live="polite">
                    <template v-if="marcado">
                        <IconeLucide :nome="marcado" class="h-5 w-5 text-cyan-700" />
                        <span>Marcado: <strong>{{ marcadoLabel }}</strong></span>
                    </template>
                    <span v-else class="text-gray-500">Nenhum ícone marcado.</span>
                </p>
                <div class="flex flex-col-reverse gap-2 sm:flex-row">
                    <button type="button"
                        class="inline-flex min-h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500"
                        @click="open = false">
                        Cancelar
                    </button>
                    <button type="button" :disabled="!marcado"
                        class="inline-flex min-h-11 items-center justify-center rounded-lg bg-cyan-600 px-4 text-sm font-semibold text-white hover:bg-cyan-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="confirmar()">
                        Confirmar
                    </button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
