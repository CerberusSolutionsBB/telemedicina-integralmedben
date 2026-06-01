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
import AppSwitch from "@/Components/ui/switch/Switch.vue";

const props = defineProps({
  open: { type: Boolean, default: false },
  patient: { type: Object, default: null },
});

const emit = defineEmits(["update:open"]);

const form = useForm({ answers: {}, status: true });

watch(
  () => props.patient,
  (patient) => {
    if (!patient) return;
    const answers = {};
    patient.answers.forEach((a) => {
      answers[a.question_id] = a.answer ?? "";
    });
    form.answers = answers;
    form.status = Boolean(patient.status);
  },
  { immediate: true }
);

const submit = () => {
  form.put(route("patients.update", props.patient.id), {
    onSuccess: () => emit("update:open", false),
  });
};
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-[560px] max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle>Editar Paciente</DialogTitle>
      </DialogHeader>

      <form @submit.prevent="submit" class="space-y-4 mt-2">
        <template v-if="patient">
          <!-- Dados do paciente -->
          <div class="rounded-lg border bg-gray-50 p-4 space-y-3">
            <div class="grid grid-cols-2 gap-3 text-sm">
              <div>
                <span class="text-xs text-gray-500">Nome</span>
                <p class="font-medium">{{ patient.nome }}</p>
              </div>
              <div>
                <span class="text-xs text-gray-500">Email</span>
                <p class="font-medium">{{ patient.email || '-' }}</p>
              </div>
              <div>
                <span class="text-xs text-gray-500">CPF</span>
                <p class="font-medium">{{ patient.cpf || '-' }}</p>
              </div>
              <div>
                <span class="text-xs text-gray-500">Sexo</span>
                <p class="font-medium">{{ patient.sexo || '-' }}</p>
              </div>
            </div>

            <div class="flex items-center gap-3 pt-2 border-t border-gray-200">
              <AppSwitch v-model="form.status" />
              <span class="text-sm font-medium" :class="form.status ? 'text-green-700' : 'text-red-700'">
                {{ form.status ? 'Ativo' : 'Inativo' }}
              </span>
            </div>
          </div>

          <!-- Respostas dos formulários -->
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
                :key="typeof opt === 'object' ? opt.value : opt"
                :value="typeof opt === 'object' ? opt.value : opt"
              >
                {{ typeof opt === 'object' ? opt.label : opt }}
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
