<script setup>
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/Components/ui/dialog";
import { AlertTriangle } from "lucide-vue-next";

/**
 * Confirmação ao sair de um formulário com alterações não salvas (useSairSemSalvar).
 */
defineProps({
    title: { type: String, default: "Sair sem salvar?" },
    description: { type: String, default: "As alterações feitas neste formulário ainda não foram salvas e serão perdidas." },
});

const open = defineModel("open", { type: Boolean, default: false });
const emit = defineEmits(["ficar", "sair"]);

// Fechar pelo X, Esc ou clique fora equivale a continuar editando.
const aoAlterar = (valor) => {
    if (!valor) emit("ficar");
};
</script>

<template>
    <Dialog :open="open" @update:open="aoAlterar">
        <DialogContent class="sm:max-w-md">
            <DialogHeader class="flex-row items-start gap-3 space-y-0 text-left">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-50 dark:bg-amber-950/60">
                    <AlertTriangle class="h-5 w-5 text-amber-700" aria-hidden="true" />
                </span>
                <div class="space-y-1">
                    <DialogTitle>{{ title }}</DialogTitle>
                    <DialogDescription>{{ description }}</DialogDescription>
                </div>
            </DialogHeader>

            <DialogFooter class="gap-2">
                <button type="button"
                    class="inline-flex min-h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500"
                    @click="emit('sair')">
                    Sair sem salvar
                </button>
                <button type="button"
                    class="inline-flex min-h-10 items-center justify-center rounded-lg bg-cyan-600 px-4 text-sm font-semibold text-white hover:bg-cyan-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 focus-visible:ring-offset-2"
                    @click="emit('ficar')">
                    Continuar editando
                </button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
