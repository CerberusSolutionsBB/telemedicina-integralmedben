<script setup>
import { Pencil, Trash2, Shield, Users } from "lucide-vue-next";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/Components/ui/table";

defineProps({
  users: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(["edit-user", "delete-user"]);

const formatDate = (date) => {
  return new Date(date).toLocaleDateString("pt-BR", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });
};

const roleName = (user) => {
  return user.roles?.[0]?.name ?? "-";
};
</script>

<template>
  <div class="overflow-x-auto">
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead class="text-center">Código</TableHead>
          <TableHead class="text-center">Nome</TableHead>
          <TableHead class="text-center">E-mail</TableHead>
          <TableHead class="text-center">Perfil</TableHead>
          <TableHead class="text-center">Criado em</TableHead>
          <TableHead class="text-center">Ações</TableHead>
        </TableRow>
      </TableHeader>

      <TableBody>
        <TableRow v-for="user in users.data" :key="user.id">
          <TableCell class="text-center font-medium">
            {{ user.id }}
          </TableCell>

          <TableCell class="text-center">
            {{ user.name }}
          </TableCell>

          <TableCell class="text-center">
            {{ user.email }}
          </TableCell>

          <TableCell class="text-center">
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-cyan-50 text-cyan-700">
              <Shield class="w-3 h-3" />
              {{ roleName(user) }}
            </span>
          </TableCell>

          <TableCell class="text-center">
            {{ formatDate(user.created_at) }}
          </TableCell>

          <TableCell class="text-center">
            <div class="flex gap-3 justify-center">
              <Pencil
                class="w-5 h-5 cursor-pointer hover:text-cyan-600"
                @click="emit('edit-user', user)"
              />
              <Trash2
                class="w-5 h-5 cursor-pointer hover:text-red-600"
                @click="emit('delete-user', user)"
              />
            </div>
          </TableCell>
        </TableRow>

        <TableRow v-if="!users.data || users.data.length === 0">
          <TableCell colspan="6" class="text-center py-8 text-gray-500">
            <div class="flex flex-col items-center gap-2">
              <Users class="w-8 h-8 text-gray-300" />
              <span>Nenhum usuário encontrado.</span>
            </div>
          </TableCell>
        </TableRow>
      </TableBody>
    </Table>
  </div>
</template>
