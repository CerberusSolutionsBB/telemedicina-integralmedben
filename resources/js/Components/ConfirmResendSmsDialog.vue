<script setup>
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/Components/ui/alert-dialog";

const props = defineProps({
  open: {
    type: Boolean,
    required: true,
  },
  recipient: {
    type: String,
    default: null,
  },
  tenantId: {
    type: String,
    default: null,
  },
});

const emit = defineEmits(["update:open", "confirm"]);
</script>

<template>
  <AlertDialog
    :open="props.open"
    @update:open="emit('update:open', $event)"
  >
    <AlertDialogContent>
      <AlertDialogHeader>
        <AlertDialogTitle>Reenviar este SMS?</AlertDialogTitle>
        <AlertDialogDescription>
          Isso vai reenviar o SMS para {{ recipient || 'o destinatário' }}, consumindo a cota de SMS do credenciado{{ tenantId ? ` ${tenantId}` : '' }}.
        </AlertDialogDescription>
      </AlertDialogHeader>

      <AlertDialogFooter>
        <AlertDialogCancel>
          Cancelar
        </AlertDialogCancel>

        <AlertDialogAction
          @click="
            emit('confirm');
            emit('update:open', false);
          "
        >
          Reenviar
        </AlertDialogAction>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
</template>
