<script setup>
import { Head } from "@inertiajs/vue3";
import TenantAdminLayout from "@/Layouts/TenantAdminLayout.vue";
import PatientsTable from "@/Components/TablePatients.vue";
import PatientImportDialog from "@/Components/PatientImportDialog.vue";
import ConfirmDeleteModal from "@/Components/ConfirmDeleteModal.vue";
import { Button } from "@/Components/ui/button";
import { Plus, Upload, FileDown } from "lucide-vue-next";
import { usePatientIndex } from "@/Composables/Patient/usePatientIndex";
import { usePermissoesBeneficiario } from "@/Composables/Patient/usePermissoesBeneficiario";

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
    // Opções dos filtros: { planos: [{ value, label }], usuarios: [{ value, label }] }
    filtrosOpcoes: {
        type: Object,
        default: () => ({ planos: [], usuarios: [] }),
    },
    // Totalizadores: { total, ativos, inativos, planos: [{ value, label, total }] }
    totais: {
        type: Object,
        default: null,
    },
    tenantPhoto: {
        type: String,
        default: null,
    },
    cartaoPacienteEnabled: {
        type: Boolean,
        default: false,
    },
    cartaoDinamicoEnabled: {
        type: Boolean,
        default: false,
    },
});

const {
    activeTab,
    openImportDialog,
    currentPatientsCount,
    newPatientsCount,
    novoPaciente,
    deleteModal,
    confirmDelete,
    cancelDelete,
    confirmDeletePatient,
} = usePatientIndex(props);

const { podeCriar } = usePermissoesBeneficiario();
</script>

<template>
    <Head title="Pacientes" />

    <TenantAdminLayout :tenant-name="tenantName" :tenant-photo="tenantPhoto">
        <div class="space-y-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold text-gray-900">
                    Beneficiário
                </h1>
                <p class="text-sm text-gray-500">
                    Gerencie os beneficiários atuais e novos cadastros.
                </p>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <Button v-if="podeCriar" size="sm" @click="novoPaciente">
                        <Plus class="w-4 h-4 mr-1" />
                        Novo
                    </Button>
                    <Button v-if="podeCriar" size="sm" variant="outline" @click="openImportDialog = true">
                        <Upload class="w-4 h-4 mr-1" />
                        Importar
                    </Button>
                </div>
                <div class="flex items-center gap-2">
                    <a v-if="podeCriar" :href="route('patients.template', 'csv')"
                        class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 bg-white text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                        <FileDown class="w-4 h-4" />
                        Template CSV
                    </a>
                    <a v-if="podeCriar" :href="route('patients.template', 'xlsx')"
                        class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 bg-white text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                        <FileDown class="w-4 h-4" />
                        Template XLSX
                    </a>
                </div>
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
                            Beneficiários atuais
                            <span class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                                {{ currentPatientsCount }}
                            </span>
                        </button>

                        <!-- <button type="button" @click="activeTab = 'new'" :class="[
                            'px-4 py-3 text-sm font-medium border-b-2 transition-colors',
                            activeTab === 'new'
                                ? 'border-cyan-500 text-cyan-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]">
                            Novos beneficiário
                            <span class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                                {{ newPatientsCount }}
                            </span>
                        </button> -->
                    </nav>
                </div>

                <div v-show="activeTab === 'current'" class="p-4">
                    <PatientsTable :patients="patients" :filtros-opcoes="filtrosOpcoes" :totais="totais" :cartao-paciente-enabled="cartaoPacienteEnabled"
                        :cartao-dinamico-enabled="cartaoDinamicoEnabled" @delete-patient="confirmDelete" />
                </div>

                <div v-show="activeTab === 'new'" class="p-4">
                    <PatientsTable :patients="newPatients" :cartao-paciente-enabled="cartaoPacienteEnabled"
                        :cartao-dinamico-enabled="cartaoDinamicoEnabled" @delete-patient="confirmDelete" />
                </div>
            </div>
        </div>
    </TenantAdminLayout>

    <PatientImportDialog v-model:open="openImportDialog" />

    <ConfirmDeleteModal
        :show="deleteModal.show"
        title="Excluir Paciente"
        :message="`Tem certeza que deseja excluir o paciente ${deleteModal.patient?.nome || ''}?`"
        :item-name="deleteModal.patient?.nome || ''"
        confirm-text="Sim, Excluir"
        cancel-text="Cancelar"
        :is-processing="deleteModal.isProcessing"
        @confirm="confirmDeletePatient"
        @close="cancelDelete"
    />
</template>
