<script setup>
import { Pencil, Trash2 } from "lucide-vue-next";
import { Link } from "@inertiajs/vue3";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/Components/ui/table";

const props = defineProps({
  patients: { type: Object, required: true },
  questions: { type: Array, default: () => [] },
});

const emit = defineEmits(["edit-patient", "delete-patient"]);

const formatDate = (date) =>
  new Date(date).toLocaleDateString("pt-BR", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });

const getAnswer = (patient, questionId) => {
  const a = patient.answers.find((a) => a.question_id === questionId);
  return a?.answer || "-";
};
</script>

<template>
  <div class="overflow-x-auto">
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead class="text-center">CPF</TableHead>
          <TableHead class="text-center">Credenciado</TableHead>
          <TableHead
            v-for="question in questions"
            :key="question.id"
            class="text-center"
          >
            <div class="truncate max-w-[140px]" :title="question.title">
              {{ question.title }}
            </div>
          </TableHead>
          <TableHead class="text-center">Cadastro</TableHead>
          <TableHead class="text-center">Ações</TableHead>
        </TableRow>
      </TableHeader>

      <TableBody>
        <TableRow v-for="patient in patients.data" :key="patient.id">
          <TableCell class="text-center font-medium">
            {{ patient.cpf }}
          </TableCell>

          <TableCell class="text-center">
            {{ patient.tenant?.name ?? "-" }}
          </TableCell>

          <TableCell
            v-for="question in questions"
            :key="question.id"
            class="text-center"
          >
            <div class="truncate max-w-[180px]" :title="getAnswer(patient, question.id)">
              {{ getAnswer(patient, question.id) }}
            </div>
          </TableCell>

          <TableCell class="text-center">
            {{ formatDate(patient.created_at) }}
          </TableCell>

          <TableCell class="text-center">
            <div class="flex gap-3 justify-center">
              <Pencil
                class="w-4 h-4 cursor-pointer hover:text-cyan-600"
                @click="emit('edit-patient', patient)"
              />
              <Trash2
                class="w-4 h-4 cursor-pointer hover:text-red-600"
                @click="emit('delete-patient', patient)"
              />
            </div>
          </TableCell>
        </TableRow>
      </TableBody>
    </Table>

    <div v-if="patients.links" class="flex gap-2 justify-center p-4">
      <template v-for="link in patients.links" :key="link.label">
        <Link
          v-if="link.url"
          :href="link.url"
          :class="[
            'px-3 py-1 rounded text-sm',
            link.active ? 'bg-cyan-600 text-white' : 'bg-gray-200',
          ]"
          v-html="link.label"
        />
        <span
          v-else
          class="px-3 py-1 rounded text-sm bg-gray-100 text-gray-400"
          v-html="link.label"
        />
      </template>
    </div>
  </div>
</template>
