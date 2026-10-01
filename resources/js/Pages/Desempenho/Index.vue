<script setup>
import { Head } from "@inertiajs/vue3";
import TenantAdminLayout from "@/Layouts/TenantAdminLayout.vue";
import ConfirmDeleteModal from "@/Components/ConfirmDeleteModal.vue";
import TableDesempenhos from "@/Components/Desempenho/TableDesempenhos.vue";
import { Button } from "@/Components/ui/button";
import { useDesempenhoIndex } from "@/Composables/Desempenho/useDesempenhoIndex";
import { Plus } from "lucide-vue-next";

const props = defineProps({
    desempenhos: { type: Array, default: () => [] },
    statusLabels: { type: Object, default: () => ({}) },
});

const {
    abas,
    aba,
    filtros,
    contagemPorAba,
    planosDisponiveis,
    filtrados,
    excluirModal,
    abrirExcluir,
    fecharExcluir,
    confirmarExcluir,
    novaMeta,
} = useDesempenhoIndex(props);

const rotuloAba = (key) => (key === "todas" ? "Todas" : props.statusLabels[key]);
</script>

<template>
    <Head title="Desempenho" />

    <TenantAdminLayout>
        <div class="space-y-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold text-gray-900">Desempenho</h1>
                <p class="text-sm text-gray-500">Gerencie as metas de registro de beneficiários dos usuários.</p>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <Button size="sm" @click="novaMeta">
                        <Plus class="w-4 h-4 mr-1" />
                        Nova
                    </Button>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow border border-gray-100">
                <div class="border-b border-gray-200 px-4">
                    <nav class="flex gap-2 overflow-x-auto" aria-label="Tabs">
                        <button v-for="key in abas" :key="key" type="button" @click="aba = key" :class="[
                            'px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
                            aba === key
                                ? 'border-cyan-500 text-cyan-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]">
                            {{ rotuloAba(key) }}
                            <span class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                                {{ contagemPorAba[key] }}
                            </span>
                        </button>
                    </nav>
                </div>

                <div class="p-4">
                    <TableDesempenhos :desempenhos="filtrados" :filtros="filtros" :planos="planosDisponiveis"
                        :status-labels="statusLabels" @excluir="abrirExcluir" />
                </div>
            </div>
        </div>
    </TenantAdminLayout>

    <ConfirmDeleteModal
        :show="excluirModal.show"
        title="Excluir Meta"
        :message="`Tem certeza que deseja excluir a meta ${excluirModal.desempenho?.titulo || ''}?`"
        :item-name="excluirModal.desempenho?.titulo || ''"
        warning-message="Os registros de beneficiários não são afetados; apenas a meta é removida."
        confirm-text="Sim, Excluir"
        cancel-text="Cancelar"
        :is-processing="excluirModal.isProcessing"
        @confirm="confirmarExcluir"
        @close="fecharExcluir"
    />
</template>
