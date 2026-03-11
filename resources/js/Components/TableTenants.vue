<script setup>
import { Settings, Pencil, Trash2, PlusCircle } from "lucide-vue-next";
import { router } from "@inertiajs/vue3";
import { ref } from "vue";
import AddSmsQuotaDialog from "@/Components/AddSmsQuotaDialog.vue";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/Components/ui/table";

const props = defineProps({
  tenants: {
    type: Array,
    required: true,
    default: () => [],
  },
});

const emit = defineEmits(["request-assign-questions", "edit-tenant"]);

const quotaDialogOpen  = ref(false);
const quotaTenant      = ref(null);

const openQuotaDialog = (tenant) => {
  quotaTenant.value = tenant;
  quotaDialogOpen.value = true;
};

const linkTenantQuestions = (tenant) => {
  emit("request-assign-questions", tenant);
};

const editTenant = (tenant) => {
  emit("edit-tenant", tenant);
};

const deleteTenant = (tenant) => {
  if (confirm(`Tem certeza que deseja excluir o tenant "${tenant.name}"?`)) {
    router.delete(route("tenants.destroy"), {
      data: { id: tenant.id }
    });
  }
};
</script>

<template>
  <Table>
    <TableHeader>
      <TableRow>
        <TableHead class="w-[100px] text-center"> Nome </TableHead>
        <TableHead class="text-center">SubDomínio</TableHead>
        <TableHead class="text-center">Pacientes</TableHead>
        <TableHead class="text-center">SMS Enviados</TableHead>
        <TableHead class="text-center">Pendentes</TableHead>
        <TableHead class="text-center">Falhas</TableHead>
        <TableHead class="text-center">Cota</TableHead>
        <TableHead class="text-center"> Ações </TableHead>
      </TableRow>
    </TableHeader>
    <TableBody>
      <TableRow v-for="tenant in tenants" :key="tenant.name">
        <TableCell class="font-medium text-center">
          {{ tenant.name }}
        </TableCell>
        <TableCell class="text-center">
          <a
            :href="`https://${tenant.tenant_domain}`"
            target="_blank"
            class="text-cyan-600 hover:text-cyan-800 hover:underline"
          >
            {{ tenant.tenant_domain }}
          </a>
        </TableCell>
        <TableCell class="text-center">
          {{ tenant.central_patients_count ?? 0 }}
        </TableCell>
        <TableCell class="text-center font-medium text-green-700">
          {{ tenant.sms_sent_count ?? 0 }}
        </TableCell>
        <TableCell class="text-center font-medium text-yellow-600">
          {{ tenant.sms_pending_count ?? 0 }}
        </TableCell>
        <TableCell class="text-center font-medium text-red-600">
          {{ tenant.sms_failed_count ?? 0 }}
        </TableCell>
        <TableCell class="text-center">
          <div class="inline-flex items-center gap-1">
            <span
              class="px-2 py-0.5 rounded-full text-xs font-semibold"
              :class="
                tenant.sms_quota === 0
                  ? 'bg-red-100 text-red-700'
                  : tenant.sms_quota <= 10
                  ? 'bg-yellow-100 text-yellow-700'
                  : 'bg-green-100 text-green-700'
              "
            >
              {{ tenant.sms_quota }} restantes
            </span>
            <button
              @click="openQuotaDialog(tenant)"
              class="text-cyan-600 hover:text-cyan-800 transition-colors"
              title="Adicionar SMS"
            >
              <PlusCircle class="w-4 h-4" />
            </button>
          </div>
        </TableCell>
        <TableCell class="text-center">
          <div class="flex items-center justify-center gap-3">
            <button
              @click="editTenant(tenant)"
              class="text-black hover:text-cyan-900 transition-colors"
              title="Editar"
            >
              <Pencil class="w-5 h-5" />
            </button>
            <button
              @click="linkTenantQuestions(tenant)"
              class="text-black hover:text-gray-900 transition-colors"
              title="Configurar Questões"
            >
              <Settings class="w-5 h-5" />
            </button>
            <button
              @click="deleteTenant(tenant)"
              class="text-black hover:text-red-900 transition-colors"
              title="Excluir"
            >
              <Trash2 class="w-5 h-5" />
            </button>
          </div>
        </TableCell>
      </TableRow>
    </TableBody>
  </Table>

  <AddSmsQuotaDialog
    v-model:open="quotaDialogOpen"
    :tenant="quotaTenant"
  />
</template>
