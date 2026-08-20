<script setup>
import { ref } from "vue";
import { Head, router } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import CentralAdminLayout from "@/Layouts/CentralAdminLayout.vue";
import TableUsers from "@/Components/TableUsers.vue";
import UserDialog from "@/Components/UserDialog.vue";
import SearchInput from "@/Components/SearchInput.vue";
import PaginationSimple from "@/Components/PaginationSimple.vue";

const props = defineProps({
  users: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({ search: "" }),
  },
});

const search = ref(props.filters.search ?? "");

const applyFilters = () => {
  router.get(
    route("central-users.index"),
    { search: search.value || undefined },
    { preserveScroll: true, preserveState: true, replace: true }
  );
};

const clearFilters = () => {
  search.value = "";
  applyFilters();
};

const openDialog = ref(false);
const selectedUser = ref(null);

const openCreateDialog = () => {
  selectedUser.value = null;
  openDialog.value = true;
};

const openEditDialog = (user) => {
  selectedUser.value = user;
  openDialog.value = true;
};

const deleteUser = (user) => {
  if (!confirm(`Deseja excluir o usuário "${user.name}"?`)) return;
  router.delete(route("central-users.destroy", user.id));
};
</script>

<template>
  <Head title="Usuários" />

  <CentralAdminLayout>
    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
      <div>
        <h1 class="text-xl sm:text-2xl font-bold">Usuários</h1>
        <p class="text-sm text-gray-500 mt-1">
          {{ users.total ?? 0 }} usuário{{ (users.total ?? 0) === 1 ? "" : "s" }} cadastrado{{ (users.total ?? 0) === 1 ? "" : "s" }}
        </p>
      </div>
      <Button @click="openCreateDialog"> Novo Usuário </Button>
    </div>

    <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm mb-4">
      <div class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
          <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
          <SearchInput
            v-model="search"
            placeholder="Buscar por nome, e-mail ou perfil..."
            width="w-full"
            @search="applyFilters"
          />
        </div>

        <Button v-if="search" variant="outline" @click="clearFilters" class="h-10">
          Limpar
        </Button>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
      <TableUsers
        :users="users"
        @edit-user="openEditDialog"
        @delete-user="deleteUser"
      />

      <PaginationSimple
        :data="users"
        :links="users.links || []"
        :has-data="(users.data?.length ?? 0) > 0"
        label="usuários"
      />
    </div>
  </CentralAdminLayout>

  <UserDialog
    v-model:open="openDialog"
    :user="selectedUser"
    store-route="central-users.store"
    update-route="central-users.update"
  />
</template>
