<script setup>
import { ref } from "vue";
import { Head, router } from "@inertiajs/vue3";
import TenantAdminLayout from "@/Layouts/TenantAdminLayout.vue";
import PatientsTable from "@/Components/TablePatients.vue";
import PatientDialog from "@/Components/PatientDialog.vue";

defineProps({
  patients: {
    type: Object,
    required: true,
  },
  tenantName: {
    type: String,
    default: "",
  },
  tenantPhoto: {
    type: String,
    default: null,
  },
});

const openDialog = ref(false);
const selectedPatient = ref(null);

const openEdit = (patient) => {
  selectedPatient.value = patient;
  openDialog.value = true;
};

const deletePatient = (patient) => {
  if (!confirm(`Deseja excluir este paciente?`)) return;
  router.delete(route("patients.destroy", patient.id));
};
</script>

<template>
  <Head title="Pacientes Cadastrados" />

  <TenantAdminLayout :tenant-name="tenantName" :tenant-photo="tenantPhoto">
    <div class="bg-white rounded-lg shadow">
      <PatientsTable
        :patients="patients"
        @edit-patient="openEdit"
        @delete-patient="deletePatient"
      />
    </div>
  </TenantAdminLayout>

  <PatientDialog
    v-model:open="openDialog"
    :patient="selectedPatient"
  />
</template>
