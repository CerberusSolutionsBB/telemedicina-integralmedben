<script setup>
import { Head } from "@inertiajs/vue3";
import TenantAdminLayout from "@/Layouts/TenantAdminLayout.vue";
import ConfirmDeleteModal from "@/Components/ConfirmDeleteModal.vue";
import TableDesempenhos from "@/Components/Desempenho/TableDesempenhos.vue";
import { Button } from "@/Components/ui/button";
import { useDesempenhoIndex } from "@/Composables/Desempenho/useDesempenhoIndex";
import RelatorioProducao from "@/Components/Desempenho/RelatorioProducao.vue";
import { useAbaAtiva } from "@/Composables/Pagina/useAbaAtiva";
import { FileBarChart, Plus, Target } from "lucide-vue-next";

const props = defineProps({
    desempenhos: { type: Array, default: () => [] },
    statusLabels: { type: Object, default: () => ({}) },
    // Opções do relatório de produção: { usuarios, perfis, planos, origens, padrao, maxDias }
    relatorioOpcoes: { type: Object, required: true },
});

// Seções da tela: Metas | Relatórios (persistida em ?secao=).
const secoes = [
    { key: "metas", label: "Metas", icon: Target },
    { key: "relatorios", label: "Relatórios", icon: FileBarChart },
];
const { activeTab: secao } = useAbaAtiva("relatorio:secao", secoes.map((s) => s.key), "metas", "secao");

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
    <Head title="Relatório" />

    <TenantAdminLayout>
        <div class="space-y-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold text-gray-900">Relatório</h1>
                <p class="text-sm text-gray-500">Acompanhe a produção da equipe e as metas de registro de beneficiários.</p>
            </div>

            <nav class="flex gap-2 border-b border-gray-200" aria-label="Seções">
                <button v-for="item in secoes" :key="item.key" type="button" @click="secao = item.key"
                    :aria-current="secao === item.key ? 'page' : undefined" :class="[
                        'inline-flex items-center gap-2 px-4 py-3 text-sm font-semibold border-b-2 -mb-px transition-colors',
                        secao === item.key
                            ? 'border-cyan-500 text-cyan-700'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    ]">
                    <component :is="item.icon" class="h-4 w-4" aria-hidden="true" />
                    {{ item.label }}
                </button>
            </nav>

            <RelatorioProducao v-if="secao === 'relatorios'" :opcoes="relatorioOpcoes" />

            <div v-if="secao === 'metas'" class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <Button size="sm" @click="novaMeta">
                        <Plus class="w-4 h-4 mr-1" />
                        Nova
                    </Button>
                </div>
            </div>

            <div v-if="secao === 'metas'" class="bg-white rounded-lg shadow border border-gray-100">
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
