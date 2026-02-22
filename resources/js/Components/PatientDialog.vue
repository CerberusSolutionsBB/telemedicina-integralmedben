<script setup>
import { useForm } from "@inertiajs/vue3";
import { watch } from "vue";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/Components/ui/dialog";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

const props = defineProps({
  open: { type: Boolean, default: false },
  patient: { type: Object, default: null },
});

const emit = defineEmits(["update:open"]);

const form = useForm({ answers: {} });

watch(
  () => props.patient,
  (patient) => {
    if (!patient) return;
    const answers = {};
    patient.answers.forEach((a) => {
      answers[a.question_id] = a.answer ?? "";
    });
    form.answers = answers;
  },
  { immediate: true }
);

const submit = () => {
  form.put(route("patients.update", props.patient.id), {
    onSuccess: () => emit("update:open", false),
  });
};

const getQuestion = (answer) => answer.question ?? null;
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-[560px] max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle>Editar Paciente</DialogTitle>
      </DialogHeader>

      <form @submit.prevent="submit" class="space-y-4 mt-2">
        <template v-if="patient">
          <div
            v-for="answer in patient.answers"
            :key="answer.question_id"
            class="space-y-1"
          >
            <Label>{{ answer.question?.title ?? `Pergunta #${answer.question_id}` }}</Label>

            <select
              v-if="answer.question?.type === 'option'"
              v-model="form.answers[answer.question_id]"
              class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
            >
              <option value="">Selecione...</option>
              <option
                v-for="opt in answer.question.options"
                :key="opt"
                :value="opt"
              >
                {{ opt }}
              </option>
            </select>

            <Input
              v-else
              :type="answer.question?.type ?? 'text'"
              v-model="form.answers[answer.question_id]"
            />
          </div>
        </template>

        <div class="flex justify-end gap-2 pt-2">
          <Button type="button" variant="outline" @click="emit('update:open', false)">
            Cancelar
          </Button>
          <Button type="submit" :disabled="form.processing">
            Salvar
          </Button>
        </div>
      </form>
    </DialogContent>
  </Dialog>
</template>
