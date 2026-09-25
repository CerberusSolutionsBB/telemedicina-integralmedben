<script setup>
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent,
    AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from "@/Components/ui/alert-dialog";

defineProps({
    title: { type: String, required: true },
    description: { type: String, default: "Essa ação não pode ser desfeita." },
    confirmLabel: { type: String, default: "Excluir" },
});

const open = defineModel("open", { type: Boolean, default: false });
const emit = defineEmits(["confirm"]);
</script>

<template>
    <AlertDialog v-model:open="open">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>{{ title }}</AlertDialogTitle>
                <AlertDialogDescription>
                    {{ description }}
                    <slot />
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Cancelar</AlertDialogCancel>
                <AlertDialogAction class="bg-red-600 text-white hover:bg-red-700" @click="emit('confirm')">
                    {{ confirmLabel }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
