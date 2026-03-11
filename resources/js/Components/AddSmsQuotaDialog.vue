<script setup>
import { watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/Components/ui/dialog";

const props = defineProps({
  open:   { type: Boolean, required: true },
  tenant: { type: Object,  default: null },
});

const emit = defineEmits(["update:open"]);

const form = useForm({
  amount: 50,
  notes:  "",
});

watch(() => props.open, (val) => {
  if (val) form.reset();
});

const close = () => emit("update:open", false);

const submit = () => {
  form.post(route("admin.tenants.add-sms-quota", props.tenant.id), {
    onSuccess: () => close(),
  });
};
</script>

<template>
  <Dialog :open="open" @update:open="close">
    <DialogContent class="sm:max-w-sm">
      <DialogHeader>
        <DialogTitle>Adicionar SMS — {{ tenant?.name }}</DialogTitle>
      </DialogHeader>

      <form @submit.prevent="submit" class="space-y-4 pt-2">
        <div class="space-y-1">
          <Label for="amount">Quantidade de SMS</Label>
          <Input
            id="amount"
            v-model.number="form.amount"
            type="number"
            min="1"
            max="10000"
            required
          />
          <p v-if="form.errors.amount" class="text-sm text-red-500">
            {{ form.errors.amount }}
          </p>
        </div>

        <div class="space-y-1">
          <Label for="notes">Observação (opcional)</Label>
          <Input
            id="notes"
            v-model="form.notes"
            type="text"
            placeholder="Ex: pacote mensal, ajuste manual..."
          />
        </div>

        <div class="flex justify-end gap-2 pt-1">
          <Button type="button" variant="secondary" @click="close">Cancelar</Button>
          <Button type="submit" :disabled="form.processing">Confirmar</Button>
        </div>
      </form>
    </DialogContent>
  </Dialog>
</template>
