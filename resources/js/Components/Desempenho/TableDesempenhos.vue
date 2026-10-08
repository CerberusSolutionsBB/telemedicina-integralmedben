<script setup>
import { router } from "@inertiajs/vue3";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui/table";
import { BARRA_CLASSES, STATUS_CLASSES } from "@/Composables/Desempenho/status";
import { Eye, Pencil, Search, Target, Trash2 } from "lucide-vue-next";

/**
 * Tabela de metas no mesmo padrão da TablePatients (filtros no topo + tabela).
 */
const props = defineProps({
    desempenhos: { type: Array, default: () => [] },
    filtros: { type: Object, required: true },
    planos: { type: Array, default: () => [] },
    statusLabels: { type: Object, default: () => ({}) },
});

const emit = defineEmits(["excluir"]);

const filtros = props.filtros;
const fmtMoeda = (valor) => Number(valor || 0).toLocaleString("pt-BR", { style: "currency", currency: "BRL" });

const cabecalho = "text-center whitespace-nowrap text-xs font-semibold text-gray-600 uppercase tracking-wider";
const campoFiltro =
    "border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-white focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition-all";
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Metas Cadastradas</h1>
                <p class="text-sm text-gray-500 mt-0.5">{{ desempenhos.length }} meta(s) encontrada(s)</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-gray-50/50">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="relative flex-1 min-w-[220px]">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <input v-model="filtros.busca" type="text" placeholder="Buscar por título ou perfil..."
                            class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-lg bg-white focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition-all" />
                    </div>

                    <select v-model="filtros.tipo" :class="campoFiltro">
                        <option value="">Todos os tipos</option>
                        <option value="individual">Individual</option>
                        <option value="coletiva">Coletiva</option>
                    </select>

                    <select v-model="filtros.plano" :class="campoFiltro">
                        <option value="">Todos os planos</option>
                        <option v-for="plano in planos" :key="plano" :value="plano">{{ plano }}</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow class="bg-gray-50/80">
                            <TableHead :class="cabecalho">Código</TableHead>
                            <TableHead :class="cabecalho">Título</TableHead>
                            <TableHead :class="cabecalho">Função</TableHead>
                            <TableHead :class="cabecalho">Plano</TableHead>
                            <TableHead :class="cabecalho">Tipo</TableHead>
                            <TableHead :class="cabecalho">Meta</TableHead>
                            <TableHead :class="cabecalho">Período</TableHead>
                            <TableHead :class="cabecalho">Perfis</TableHead>
                            <TableHead :class="cabecalho">Progresso</TableHead>
                            <TableHead :class="cabecalho">Status</TableHead>
                            <TableHead :class="cabecalho">Ações</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <TableRow v-for="item in desempenhos" :key="item.id" class="hover:bg-gray-50/50 transition-colors">
                            <TableCell class="text-center">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 text-xs font-bold text-gray-600">
                                    {{ item.id }}
                                </span>
                            </TableCell>

                            <TableCell class="text-center font-medium text-gray-900">
                                <div class="max-w-[220px] truncate mx-auto" :title="item.titulo">{{ item.titulo }}</div>
                            </TableCell>

                            <TableCell class="text-center text-sm text-gray-600 whitespace-nowrap">{{ item.funcao }}</TableCell>

                            <TableCell class="text-center text-sm text-gray-600 whitespace-nowrap">{{ item.plano }}</TableCell>

                            <TableCell class="text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium"
                                    :class="item.tipo_meta === 'coletiva' ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-blue-50 text-blue-700 border border-blue-200'">
                                    {{ item.tipo_label }}
                                </span>
                            </TableCell>

                            <TableCell class="text-center font-semibold text-gray-900">
                                {{ item.meta }}
                                <span v-if="item.funcao_key === 'comissao_venda_plano' && item.meta_valor" class="block text-[11px] font-normal text-gray-500">
                                    {{ fmtMoeda(item.meta_valor) }}
                                </span>
                            </TableCell>

                            <TableCell class="text-center text-sm text-gray-500 whitespace-nowrap">
                                {{ item.data_inicio }} a {{ item.prazo }}
                            </TableCell>

                            <TableCell class="text-center text-sm text-gray-600">
                                <div class="max-w-[180px] truncate mx-auto" :title="item.roles.join(', ')">{{ item.roles.join(', ') || '-' }}</div>
                            </TableCell>

                            <TableCell class="text-center">
                                <div class="flex items-center gap-2 min-w-[140px]">
                                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100">
                                        <div class="h-full rounded-full" :class="BARRA_CLASSES[item.progresso.status]"
                                            :style="{ width: `${item.progresso.percentual}%` }" />
                                    </div>
                                    <span class="w-10 text-right text-xs font-medium text-gray-700">{{ item.progresso.percentual }}%</span>
                                </div>
                                <p class="mt-1 text-[11px] text-gray-500 whitespace-nowrap">
                                    <template v-if="item.funcao_key === 'comissao_venda_plano'">
                                        {{ item.progresso.total }} de {{ item.meta }} vendas
                                    </template>
                                    <template v-else-if="item.tipo_meta === 'coletiva'">{{ item.progresso.total }} de {{ item.meta }} registros</template>
                                    <template v-else>{{ item.progresso.atingiram }} de {{ item.progresso.participantes }} usuário(s)</template>
                                </p>
                                <p v-if="item.funcao_key === 'comissao_venda_plano' && item.meta_valor" class="text-[11px] text-gray-500 whitespace-nowrap">
                                    {{ fmtMoeda(item.progresso.valor) }} de {{ fmtMoeda(item.meta_valor) }}
                                </p>
                            </TableCell>

                            <TableCell class="text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap"
                                    :class="STATUS_CLASSES[item.progresso.status]">
                                    {{ statusLabels[item.progresso.status] }}
                                </span>
                            </TableCell>

                            <TableCell class="text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button type="button" @click="router.visit(route('desempenho.show', item.id))"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-cyan-600 hover:bg-cyan-50 transition-all" title="Ver detalhes">
                                        <Eye class="w-4 h-4" />
                                    </button>
                                    <button type="button" @click="router.visit(route('desempenho.edit', item.id))"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-cyan-600 hover:bg-cyan-50 transition-all" title="Editar">
                                        <Pencil class="w-4 h-4" />
                                    </button>
                                    <button type="button" @click="emit('excluir', item)"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-all" title="Excluir">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </TableCell>
                        </TableRow>

                        <TableRow v-if="!desempenhos.length">
                            <TableCell colspan="11" class="py-12 text-center">
                                <Target class="mx-auto h-10 w-10 text-gray-300" />
                                <p class="mt-3 font-medium text-gray-900">Nenhuma meta encontrada</p>
                                <p class="mt-1 text-sm text-gray-500">Ajuste os filtros ou crie uma nova meta.</p>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </div>
</template>
