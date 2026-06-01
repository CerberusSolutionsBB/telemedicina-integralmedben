<script setup>
import { ref, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/Components/ui/dialog";
import { Button } from "@/Components/ui/button";
import { Label } from "@/Components/ui/label";
import { Upload, FileSpreadsheet, AlertCircle, CheckCircle2 } from "lucide-vue-next";

const props = defineProps({
  open: { type: Boolean, default: false },
});

const emit = defineEmits(["update:open"]);

const form = useForm({
  file: null,
});

const dragOver = ref(false);
const filePreview = ref(null);
const fileError = ref(null);

const allowedExtensions = [".csv", ".txt", ".xlsx", ".xls"];
const allowedMimes = [
  "text/csv",
  "text/plain",
  "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
  "application/vnd.ms-excel",
];

watch(
  () => props.open,
  (val) => {
    if (!val) {
      form.reset();
      filePreview.value = null;
      fileError.value = null;
    }
  }
);

const onFileSelect = (event) => {
  const file = event.target.files?.[0];
  if (file) validateAndSet(file);
};

const onDrop = (event) => {
  dragOver.value = false;
  const file = event.dataTransfer?.files?.[0];
  if (file) validateAndSet(file);
};

const validateAndSet = (file) => {
  fileError.value = null;
  filePreview.value = null;

  const ext = "." + file.name.split(".").pop().toLowerCase();
  if (!allowedExtensions.includes(ext)) {
    fileError.value = "Formato inválido. Use CSV, XLSX ou XLS.";
    return;
  }

  if (file.size > 10 * 1024 * 1024) {
    fileError.value = "Arquivo muito grande. Máximo 10MB.";
    return;
  }

  form.file = file;
  filePreview.value = {
    name: file.name,
    size: (file.size / 1024).toFixed(1) + " KB",
  };
};

const submit = () => {
  if (!form.file) return;

  form.post(route("patients.import"), {
    onSuccess: () => {
      emit("update:open", false);
    },
  });
};

const formatHint = `ID,Data de Cadastro,Pergunta 1,Pergunta 2,...`;
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-[520px]">
      <DialogHeader>
        <DialogTitle>Importar Pacientes</DialogTitle>
        <DialogDescription>
          Envie um arquivo CSV ou XLSX com as respostas dos pacientes.
          A primeira linha deve conter os títulos das perguntas como cabeçalho.
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="submit" class="space-y-4">
        <div
          @drop.prevent="onDrop"
          @dragover.prevent="dragOver = true"
          @dragleave.prevent="dragOver = false"
          :class="[
            'border-2 border-dashed rounded-lg p-8 text-center transition-colors cursor-pointer',
            dragOver
              ? 'border-cyan-400 bg-cyan-50'
              : fileError
                ? 'border-red-300 bg-red-50'
                : 'border-gray-300 hover:border-gray-400',
          ]"
          @click="$refs.fileInput?.click()"
        >
          <input
            ref="fileInput"
            type="file"
            accept=".csv,.txt,.xlsx,.xls"
            class="hidden"
            @change="onFileSelect"
          />

          <div v-if="!filePreview" class="space-y-2">
            <Upload class="w-10 h-10 mx-auto text-gray-400" />
            <p class="text-sm text-gray-600">
              <span class="font-semibold text-cyan-600">Clique para selecionar</span>
              ou arraste o arquivo aqui
            </p>
            <p class="text-xs text-gray-400">CSV, XLSX ou XLS até 10MB</p>
          </div>

          <div v-else class="space-y-2">
            <FileSpreadsheet class="w-10 h-10 mx-auto text-green-500" />
            <p class="text-sm font-medium text-gray-800">{{ filePreview.name }}</p>
            <p class="text-xs text-gray-500">{{ filePreview.size }}</p>
          </div>
        </div>

        <div v-if="fileError" class="flex items-start gap-2 text-sm text-red-600 bg-red-50 rounded-lg p-3">
          <AlertCircle class="w-4 h-4 mt-0.5 shrink-0" />
          <span>{{ fileError }}</span>
        </div>

        <div class="bg-gray-50 rounded-lg p-3 text-xs text-gray-500 space-y-1">
          <p class="font-medium text-gray-700">Formato esperado:</p>
          <code class="block text-xs bg-white p-2 rounded border break-all">
            {{ formatHint }}
          </code>
          <p class="mt-1">
            As colunas devem corresponder exatamente aos títulos das perguntas cadastradas.
          </p>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <Button type="button" variant="outline" @click="emit('update:open', false)">
            Cancelar
          </Button>
          <Button type="submit" :disabled="form.processing || !form.file">
            <CheckCircle2 v-if="form.processing" class="w-4 h-4 mr-1 animate-spin" />
            {{ form.processing ? 'Importando...' : 'Importar' }}
          </Button>
        </div>
      </form>
    </DialogContent>
  </Dialog>
</template>
