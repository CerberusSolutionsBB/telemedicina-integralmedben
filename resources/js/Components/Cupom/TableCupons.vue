<script setup>
import { computed, reactive } from "vue";
import { Link } from "@inertiajs/vue3";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui/table";
import ConfirmDeleteModal from "@/Components/ConfirmDeleteModal.vue";
import SituacaoCupom from "@/Components/Cupom/SituacaoCupom.vue";
import { SITUACOES, fmtDesconto } from "@/Composables/Cupom/situacao";
import { useAcoesCupom } from "@/Composables/Cupom/useAcoesCupom";
import { Eye, FileDown, Pencil, Search, Ticket, Trash2 } from "lucide-vue-next";

/**
 * Lista de cupons com busca e filtro de situação (central e parceiro).
 * `rotas` = { show, edit, status, pdf, destroy } (nomes das rotas); `mostrarParceiro` só no central.
 * A situação é trocada pelo botão da coluna Situação; toda ação que muda o cupom pede confirmação.
 */
const props = defineProps({
    cupons: { type: Array, default: () => [] },
    rotas: { type: Object, required: true },
    mostrarParceiro: { type: Boolean, default: false },
});

const filtros = reactive({ busca: "", situacao: "" });

const filtrados = computed(() => {
    const busca = filtros.busca.trim().toLowerCase();

    return props.cupons.filter((cupom) =>
        (!filtros.situacao || cupom.situacao === filtros.situacao)
        && (!busca || cupom.codigo.toLowerCase().includes(busca) || (cupom.parceiro ?? "").toLowerCase().includes(busca)));
});

const { pendente, acaoPendente, processando, pedir, fechar, confirmar } = useAcoesCupom(props.rotas);

const cabecalho = "text-center whitespace-nowrap text-xs font-semibold text-gray-600 uppercase tracking-wider";
const campoFiltro = "border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-white focus:ring-2 focus:ring-cyan-500 outline-none";
const acao = "inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100";
const acaoCartao = "inline-flex min-h-11 items-center justify-center gap-1.5 rounded-lg border border-gray-200 text-sm font-medium text-gray-700 hover:bg-gray-50";
</script>

<template>
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gray-50/50">
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-[220px]">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input v-model="filtros.busca" type="text" :placeholder="mostrarParceiro ? 'Buscar por código ou parceiro...' : 'Buscar por código...'"
                        class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-lg bg-white focus:ring-2 focus:ring-cyan-500 outline-none" />
                </div>
                <select v-model="filtros.situacao" :class="campoFiltro">
                    <option value="">Todas as situações</option>
                    <option v-for="(situacao, chave) in SITUACOES" :key="chave" :value="chave">{{ situacao.label }}</option>
                </select>
            </div>
        </div>

        <div v-if="!filtrados.length" class="p-10 text-center">
            <Ticket class="mx-auto h-10 w-10 text-gray-300" aria-hidden="true" />
            <p class="mt-2 font-medium text-gray-900">Nenhum cupom encontrado</p>
        </div>

        <!-- Celular: um cartão por cupom (a tabela esconderia situação e ações fora da tela). -->
        <ul v-else class="divide-y divide-gray-100 md:hidden">
            <li v-for="cupom in filtrados" :key="cupom.id" class="space-y-3 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <Link :href="route(rotas.show, cupom.id)" class="block truncate font-mono text-base font-semibold text-gray-900 hover:text-cyan-700">{{ cupom.codigo }}</Link>
                        <p class="text-sm text-gray-600">
                            <span class="font-semibold text-cyan-700">{{ fmtDesconto(cupom) }}</span>
                            <template v-if="mostrarParceiro"> · {{ cupom.parceiro ?? 'Global' }}</template>
                        </p>
                    </div>
                    <SituacaoCupom :cupom="cupom" @pedir="(tipo) => pedir(cupom, tipo)" />
                </div>

                <dl class="grid grid-cols-2 gap-2 text-xs">
                    <div>
                        <dt class="text-gray-500">Validade</dt>
                        <dd class="text-gray-800">{{ cupom.periodo }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Usos</dt>
                        <dd class="text-gray-800">{{ cupom.usos }}<template v-if="cupom.limite_uso_total"> / {{ cupom.limite_uso_total }}</template></dd>
                    </div>
                </dl>

                <div class="grid grid-cols-4 gap-2">
                    <Link :href="route(rotas.show, cupom.id)" :class="acaoCartao">
                        <Eye class="h-4 w-4" aria-hidden="true" /> Ver
                    </Link>
                    <Link :href="route(rotas.edit, cupom.id)" :class="acaoCartao">
                        <Pencil class="h-4 w-4" aria-hidden="true" /> Editar
                    </Link>
                    <a :href="route(rotas.pdf, cupom.id)" target="_blank" rel="noopener" :class="acaoCartao">
                        <FileDown class="h-4 w-4" aria-hidden="true" /> PDF
                    </a>
                    <button type="button" :disabled="!cupom.pode_excluir"
                        :class="[acaoCartao, 'text-red-600 disabled:cursor-not-allowed disabled:opacity-40']"
                        :title="cupom.pode_excluir ? 'Excluir' : 'Cupom já usado: cancele em vez de excluir'"
                        @click="pedir(cupom, 'excluir')">
                        <Trash2 class="h-4 w-4" aria-hidden="true" /> Excluir
                    </button>
                </div>
            </li>
        </ul>

        <div v-if="filtrados.length" class="hidden overflow-x-auto md:block">
            <Table>
                <TableHeader>
                    <TableRow class="bg-gray-50/80">
                        <TableHead :class="cabecalho">Código</TableHead>
                        <TableHead :class="cabecalho">Desconto</TableHead>
                        <TableHead v-if="mostrarParceiro" :class="cabecalho">Parceiro</TableHead>
                        <TableHead :class="cabecalho">Validade</TableHead>
                        <TableHead :class="cabecalho">Usos</TableHead>
                        <TableHead :class="cabecalho">Situação</TableHead>
                        <TableHead :class="cabecalho">Ações</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="cupom in filtrados" :key="cupom.id" class="hover:bg-gray-50/50">
                        <TableCell class="text-center font-mono font-semibold">
                            <Link :href="route(rotas.show, cupom.id)" class="text-gray-900 hover:text-cyan-700 hover:underline">{{ cupom.codigo }}</Link>
                        </TableCell>
                        <TableCell class="text-center text-sm text-gray-700 whitespace-nowrap">{{ fmtDesconto(cupom) }}</TableCell>
                        <TableCell v-if="mostrarParceiro" class="text-center text-sm text-gray-600">{{ cupom.parceiro ?? 'Global' }}</TableCell>
                        <TableCell class="text-center text-xs text-gray-500 whitespace-nowrap">{{ cupom.periodo }}</TableCell>
                        <TableCell class="text-center text-sm text-gray-700 whitespace-nowrap">
                            {{ cupom.usos }}<template v-if="cupom.limite_uso_total"> / {{ cupom.limite_uso_total }}</template>
                        </TableCell>
                        <TableCell class="text-center">
                            <SituacaoCupom :cupom="cupom" @pedir="(tipo) => pedir(cupom, tipo)" />
                        </TableCell>
                        <TableCell class="text-center">
                            <div class="flex items-center justify-center gap-1">
                                <Link :href="route(rotas.show, cupom.id)" :class="acao" title="Ver detalhes" aria-label="Ver detalhes">
                                    <Eye class="h-4 w-4" />
                                </Link>
                                <Link :href="route(rotas.edit, cupom.id)" :class="acao" title="Editar" aria-label="Editar">
                                    <Pencil class="h-4 w-4" />
                                </Link>
                                <a :href="route(rotas.pdf, cupom.id)" target="_blank" rel="noopener" :class="acao"
                                    title="Gerar PDF do cupom" aria-label="Gerar PDF do cupom">
                                    <FileDown class="h-4 w-4" />
                                </a>
                                <!-- Cupom já usado não pode ser excluído (o histórico das vendas depende dele). -->
                                <button type="button" :class="[acao, 'hover:text-red-600 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent disabled:hover:text-gray-500']"
                                    :disabled="!cupom.pode_excluir"
                                    :title="cupom.pode_excluir ? 'Excluir' : 'Cupom já usado: cancele em vez de excluir'"
                                    aria-label="Excluir" @click="pedir(cupom, 'excluir')">
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <ConfirmDeleteModal :show="!!pendente" :title="acaoPendente?.titulo"
            :message="pendente ? acaoPendente.mensagem(pendente.cupom) : ''" :warning-message="acaoPendente?.aviso"
            :confirm-text="acaoPendente?.confirmar" cancel-text="Voltar" :variant="acaoPendente?.variante ?? 'danger'"
            :isProcessing="processando" @close="fechar" @confirm="confirmar" />
    </div>
</template>
