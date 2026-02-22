<script setup>
import { ref } from "vue";
import { Head, router } from "@inertiajs/vue3";
import CentralAdminLayout from "@/Layouts/CentralAdminLayout.vue";
import TableCentralPatients from "@/Components/TableCentralPatients.vue";
import CentralPatientDialog from "@/Components/CentralPatientDialog.vue";

defineProps({
  patients: { type: Object, required: true },
  questions: { type: Array, default: () => [] },
});

const openDialog = ref(false);
const selectedPatient = ref(null);

const openEdit = (patient) => {
  selectedPatient.value = patient;
  openDialog.value = true;
};

const deletePatient = (patient) => {
  if (!confirm(`Deseja excluir o paciente com CPF "${patient.cpf}"?`)) return;
  router.delete(route("central-patients.destroy", patient.id));
};
</script>

<template>
  <Head title="Pacientes" />

  <CentralAdminLayout>
    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
      <h1 class="text-xl sm:text-2xl font-bold">Pacientes</h1>
    </div>

    <div class="bg-white border rounded-xl border-gray-200 shadow-sm overflow-hidden">
      <TableCentralPatients
        :patients="patients"
        :questions="questions"
        @edit-patient="openEdit"
        @delete-patient="deletePatient"
      />
    </div>
  </CentralAdminLayout>

  <CentralPatientDialog
    v-model:open="openDialog"
    :patient="selectedPatient"
    :questions="questions"
  />
</template>
