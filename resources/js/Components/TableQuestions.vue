<script setup>
import { Pencil, Trash2, Eye } from "lucide-vue-next";
import { router } from "@inertiajs/vue3";
import Table from "./ui/table/Table.vue";
import TableHeader from "./ui/table/TableHeader.vue";
import TableRow from "./ui/table/TableRow.vue";
import TableHead from "./ui/table/TableHead.vue";
import TableBody from "./ui/table/TableBody.vue";
import TableCell from "./ui/table/TableCell.vue";

const props = defineProps({
  questions: {
    type: Array,
    required: true,
    default: () => [],
  },
});

const emit = defineEmits(["edit-question", "view-question"]);

const editQuestion = (question) => {
  emit("edit-question", question);
};

const viewQuestion = (question) => {
  emit("view-question", question);
};

const deleteQuestion = (id) => {
  if (confirm("Tem certeza que deseja deletar esta pergunta?")) {
    router.delete(route("questions.destroy", id));
  }
};

const formatType = (type) => {
  const types = {
    text: "Texto",
    email: "Email",
    number: "Número",
    tel: "Telefone",
    date: "Data",
    option: "Seleção",
  };
  return types[type] || type;
};
</script>

<template>
  <Table>
    <TableHeader>
      <TableRow>
        <TableHead class="w-[100px] text-center">ID</TableHead>
        <TableHead class="text-center">Título</TableHead>
        <TableHead class="text-center">Tipo</TableHead>
        <TableHead class="text-center">Ações</TableHead>
      </TableRow>
    </TableHeader>
    <TableBody>
      <TableRow v-for="question in questions" :key="question.id">
        <TableCell class="font-medium text-center">
          #{{ question.id }}
        </TableCell>
        <TableCell class="text-center">
          {{ question.title }}
        </TableCell>
        <TableCell class="text-center">
          <span class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800">
            {{ formatType(question.type) }}
          </span>
        </TableCell>
        <TableCell class="text-center">
          <div class="flex justify-center items-center gap-2">
            <span
              v-if="question.role"
              class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700"
              title="Pergunta essencial do sistema — não pode ser editada ou excluída"
            >
              Sistema
            </span>
            <button
              @click="viewQuestion(question)"
              class="text-black hover:text-gray-900 transition-colors"
              title="Visualizar"
            >
              <Eye class="w-4 h-4" />
            </button>
            <template v-if="!question.role">
              <button
                @click="editQuestion(question)"
                class="text-black hover:text-cyan-900 transition-colors"
                title="Editar"
              >
                <Pencil class="w-4 h-4" />
              </button>
              <button
                @click="deleteQuestion(question.id)"
                class="text-black hover:text-red-900 transition-colors"
                title="Deletar"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </template>
          </div>
        </TableCell>
      </TableRow>
    </TableBody>
  </Table>
</template>
