<script setup>
import { ref, computed } from "vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import TenantAdminLayout from "@/Layouts/TenantAdminLayout.vue";
import PatientsTable from "@/Components/TablePatients.vue";
import PatientDialog from "@/Components/PatientDialog.vue";

const props = defineProps({
    patients: {
        type: Object,
        required: true,
    },
    newPatients: {
        type: Object,
        default: () => ({
            data: [],
            links: [],
            total: 0,
        }),
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

const page = usePage();

const activeTab = ref("current");
const openDialog = ref(false);
const selectedPatient = ref(null);

const currentPatientsCount = computed(() => props.patients?.total ?? props.patients?.data?.length ?? 0);
const newPatientsCount = computed(() => props.newPatients?.total ?? props.newPatients?.data?.length ?? 0);

const openEdit = (patient) => {
    selectedPatient.value = patient;
    openDialog.value = true;
};

const deletePatient = (patient) => {
    if (!confirm(`Deseja excluir este paciente?`)) return;

    router.delete(route("cpanel.patients.destroy", patient.id));
};
</script>

<template>

    <Head title="Pacientes" />

    <TenantAdminLayout :tenant-name="tenantName" :tenant-photo="tenantPhoto">
        <div class="space-y-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold text-gray-900">
                    Pacientes
                </h1>
                <p class="text-sm text-gray-500">
                    Gerencie os pacientes atuais e novos cadastros.
                </p>
            </div>

            <div class="bg-white rounded-lg shadow border border-gray-100">
                <div class="border-b border-gray-200 px-4">
                    <nav class="flex gap-2 overflow-x-auto" aria-label="Tabs">
                        <button type="button" @click="activeTab = 'current'" :class="[
                            'px-4 py-3 text-sm font-medium border-b-2 transition-colors',
                            activeTab === 'current'
                                ? 'border-cyan-500 text-cyan-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]">
                            Pacientes atuais
                            <span class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                                {{ currentPatientsCount }}
                            </span>
                        </button>

                        <button type="button" @click="activeTab = 'new'" :class="[
                            'px-4 py-3 text-sm font-medium border-b-2 transition-colors',
                            activeTab === 'new'
                                ? 'border-cyan-500 text-cyan-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]">
                            Novos pacientes
                            <span class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                                {{ newPatientsCount }}
                            </span>
                        </button>
                    </nav>
                </div>

                <div v-show="activeTab === 'current'" class="p-4">
                    <PatientsTable :patients="patients" @edit-patient="openEdit" @delete-patient="deletePatient" />
                </div>

                <div v-show="activeTab === 'new'" class="p-4">
                    <PatientsTable :patients="newPatients" @edit-patient="openEdit" @delete-patient="deletePatient" />
                </div>
            </div>
        </div>
    </TenantAdminLayout>

    <PatientDialog v-model:open="openDialog" :patient="selectedPatient" />
</template>
