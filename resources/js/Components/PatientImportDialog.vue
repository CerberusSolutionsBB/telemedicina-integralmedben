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
import { Upload, FileSpreadsheet, AlertCircle, Download, Loader2 } from "lucide-vue-next";

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

const allowedExtensions = [".xlsx", ".xls"];

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
    fileError.value = "Envie a planilha em Excel (.xlsx ou .xls).";
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
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-[560px]">
      <DialogHeader>
        <DialogTitle>Importar Pacientes</DialogTitle>
        <DialogDescription>
          Baixe o modelo em Excel, preencha um beneficiário por linha e envie a planilha.
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="submit" class="space-y-4">
        <!-- Passo 1: modelo -->
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 rounded-lg border border-cyan-200 bg-cyan-50/60 p-4">
          <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-cyan-600 text-xs font-bold text-white">1</span>
          <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-gray-900">Baixe o modelo em Excel</p>
            <p class="text-xs text-gray-600">
              Colunas já configuradas: datas, listas de Sexo e Status e as perguntas do seu formulário.
              Nome e Data de Nascimento são obrigatórios.
            </p>
          </div>
          <a :href="route('patients.template', 'xlsx')" download
            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-700 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 focus-visible:ring-offset-2">
            <Download class="w-4 h-4" />
            Baixar modelo
          </a>
        </div>

        <!-- Passo 2: envio -->
        <div class="flex items-center gap-3">
          <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-cyan-600 text-xs font-bold text-white">2</span>
          <p class="text-sm font-semibold text-gray-900">Envie a planilha preenchida</p>
        </div>

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
            accept=".xlsx,.xls"
            class="hidden"
            @change="onFileSelect"
          />

          <div v-if="!filePreview" class="space-y-2">
            <Upload class="w-10 h-10 mx-auto text-gray-400" />
            <p class="text-sm text-gray-600">
              <span class="font-semibold text-cyan-600">Clique para selecionar</span>
              ou arraste o arquivo aqui
            </p>
            <p class="text-xs text-gray-400">Excel (.xlsx ou .xls) até 10MB</p>
          </div>

          <div v-else class="space-y-2">
            <FileSpreadsheet class="w-10 h-10 mx-auto text-green-500" />
            <p class="text-sm font-medium text-gray-800">{{ filePreview.name }}</p>
            <p class="text-xs text-gray-500">{{ filePreview.size }}</p>
          </div>
        </div>

        <div v-if="fileError || form.errors.file" class="flex items-start gap-2 text-sm text-red-600 bg-red-50 rounded-lg p-3">
          <AlertCircle class="w-4 h-4 mt-0.5 shrink-0" />
          <span>{{ fileError || form.errors.file }}</span>
        </div>

        <p class="text-xs text-gray-500">
          Linhas com erro (sem nome, data inválida, CPF já cadastrado…) são ignoradas e listadas
          ao final; as demais são importadas.
        </p>

        <div class="flex justify-end gap-2 pt-2">
          <Button type="button" variant="outline" @click="emit('update:open', false)">
            Cancelar
          </Button>
          <Button type="submit" :disabled="form.processing || !form.file">
            <Loader2 v-if="form.processing" class="w-4 h-4 mr-1 animate-spin" />
            {{ form.processing ? 'Importando...' : 'Importar' }}
          </Button>
        </div>
      </form>
    </DialogContent>
  </Dialog>
</template>
