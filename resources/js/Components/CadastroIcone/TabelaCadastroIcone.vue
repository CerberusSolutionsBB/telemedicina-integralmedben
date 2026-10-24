<script setup>
import { computed, reactive, ref, watch } from "vue";
import { Link } from "@inertiajs/vue3";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui/table";
import ConfirmDeleteModal from "@/Components/ConfirmDeleteModal.vue";
import IconeLucide from "@/Components/Icone/IconeLucide.vue";
import { useAcoesCadastroIcone } from "@/Composables/CadastroIcone/useAcoesCadastroIcone";
import { maiuscula, rotaDe } from "@/Composables/CadastroIcone/modulos";
import { CheckCircle2, CircleOff, Eye, FileDown, LayoutGrid, List, Pencil, Plus, Power, Search, SearchX, Trash2, X } from "lucide-vue-next";

/**
 * Lista dos cadastros com ícone (serviços, convênios, cupons de benefício, processos).
 * O módulo pode trocar a coluna "Detalhes" (semDetalhes) por colunas próprias (colunas). Busca e
 * filtro de status guardados na URL, contagem de resultados, estados vazios com o que
 * fazer, e cartões no celular. Ativar, inativar e excluir pedem confirmação.
 */
const props = defineProps({
    modulo: { type: Object, required: true },
    itens: { type: Array, default: () => [] },
    // Visualização ao abrir, se a URL não disser outra: "lista" ou "grade".
    visaoPadrao: { type: String, default: "lista" },
});

const rota = (acao) => rotaDe(props.modulo, acao);

// Filtros na query string: voltar para a tela mantém a busca.
const url = new URL(window.location.href);
const filtros = reactive({ busca: url.searchParams.get("busca") ?? "", status: url.searchParams.get("status") ?? "" });
// Visualização: lista (tabela) ou grade de cartões. Fica na URL quando difere do padrão.
const visao = ref(["lista", "grade"].includes(url.searchParams.get("visao")) ? url.searchParams.get("visao") : props.visaoPadrao);

watch([filtros, visao], () => {
    const atual = new URL(window.location.href);
    Object.entries({ ...filtros, visao: visao.value === props.visaoPadrao ? "" : visao.value })
        .forEach(([chave, valor]) => (valor ? atual.searchParams.set(chave, valor) : atual.searchParams.delete(chave)));
    window.history.replaceState(window.history.state, "", atual);
});

const filtrando = computed(() => !!(filtros.busca.trim() || filtros.status));
const limparFiltros = () => Object.assign(filtros, { busca: "", status: "" });

const normalizar = (texto) => String(texto ?? "").normalize("NFD").replace(/[̀-ͯ]/g, "").toLowerCase();

const filtrados = computed(() => {
    const busca = normalizar(filtros.busca.trim());

    return props.itens.filter((item) =>
        (filtros.status === "" || String(item.status) === filtros.status)
        && (!busca || [item.titulo, item.descricao, item.codigo].some((texto) => normalizar(texto).includes(busca))));
});

const { pendente, acaoPendente, processando, pedir, fechar, confirmar } = useAcoesCadastroIcone(props.modulo);

const alternar = (item) => pedir(item, item.status ? "inativar" : "ativar");

const cabecalho = "whitespace-nowrap text-xs font-semibold uppercase tracking-wider text-gray-600";
const foco = "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 focus-visible:ring-offset-2";
const acao = `inline-flex h-11 w-11 items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 hover:text-gray-900 ${foco}`;
const acaoCartao = `inline-flex min-h-11 items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 ${foco}`;
const botaoVisao = (ativa) => [
    `inline-flex min-h-11 items-center gap-1.5 px-3 text-sm font-medium ${foco}`,
    ativa ? "bg-cyan-600 text-white" : "bg-white text-gray-700 hover:bg-gray-50",
];
const etiqueta = (ativo) => [
    "inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-semibold",
    ativo
        ? "border-green-200 bg-green-50 text-green-700 dark:border-green-900 dark:bg-green-950/40 dark:text-green-300"
        : "border-gray-300 bg-gray-100 text-gray-700",
];
</script>

<template>
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <!-- Filtros -->
        <div class="space-y-3 border-b border-gray-100 bg-gray-50/50 p-4">
            <div class="flex flex-col gap-3 sm:flex-row">
                <div class="relative flex-1">
                    <label for="busca" class="sr-only">Buscar {{ modulo.plural }}</label>
                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" aria-hidden="true" />
                    <input id="busca" v-model="filtros.busca" type="search" :placeholder="modulo.buscaPlaceholder ?? `Buscar ${modulo.singular} pelo título ou descrição`"
                        class="w-full min-h-11 rounded-lg border border-gray-300 bg-white pl-9 pr-4 text-base placeholder:text-gray-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 sm:text-sm" />
                </div>
                <label for="filtro-status" class="sr-only">Filtrar por status</label>
                <select id="filtro-status" v-model="filtros.status"
                    class="min-h-11 rounded-lg border border-gray-300 bg-white px-3 text-base focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 sm:w-48 sm:text-sm">
                    <option value="">Todos os status</option>
                    <option value="true">Só ativos</option>
                    <option value="false">Só inativos</option>
                </select>
                <div class="inline-flex shrink-0 overflow-hidden rounded-lg border border-gray-300" role="group" aria-label="Visualização">
                    <button type="button" :class="botaoVisao(visao === 'grade')" :aria-pressed="visao === 'grade'" title="Ver em grade" @click="visao = 'grade'">
                        <LayoutGrid class="h-4 w-4" aria-hidden="true" /> Grade
                    </button>
                    <button type="button" :class="[botaoVisao(visao === 'lista'), 'border-l border-gray-300']" :aria-pressed="visao === 'lista'" title="Ver em lista" @click="visao = 'lista'">
                        <List class="h-4 w-4" aria-hidden="true" /> Lista
                    </button>
                </div>
            </div>

            <div v-if="itens.length" class="flex flex-wrap items-center justify-between gap-2 text-sm" aria-live="polite">
                <p class="text-gray-700">
                    <template v-if="filtrando">Mostrando <strong>{{ filtrados.length }}</strong> de {{ itens.length }} {{ modulo.plural }}</template>
                    <template v-else><strong>{{ itens.length }}</strong> {{ itens.length === 1 ? modulo.singular : modulo.plural }} no total</template>
                </p>
                <button v-if="filtrando" type="button" @click="limparFiltros"
                    :class="['inline-flex min-h-11 items-center gap-1 rounded-lg px-3 font-medium text-cyan-700 hover:bg-cyan-50', foco]">
                    <X class="h-4 w-4" aria-hidden="true" /> Limpar filtros
                </button>
            </div>
        </div>

        <!-- Nenhum cadastro ainda -->
        <div v-if="!itens.length" class="px-4 py-12 text-center">
            <IconeLucide nome="" class="mx-auto h-10 w-10 text-gray-400" />
            <p class="mt-3 text-base font-semibold text-gray-900">Nenhum {{ modulo.singular }} cadastrado ainda</p>
            <p class="mt-1 text-sm text-gray-600">Comece cadastrando o primeiro. Leva menos de um minuto.</p>
            <Link :href="route(rota('create'))"
                :class="['mt-4 inline-flex min-h-11 items-center gap-2 rounded-lg bg-cyan-600 px-5 text-sm font-semibold text-white hover:bg-cyan-700', foco]">
                <Plus class="h-4 w-4" aria-hidden="true" /> {{ modulo.novo }}
            </Link>
        </div>

        <!-- Filtros sem resultado -->
        <div v-else-if="!filtrados.length" class="px-4 py-12 text-center">
            <SearchX class="mx-auto h-10 w-10 text-gray-400" aria-hidden="true" />
            <p class="mt-3 text-base font-semibold text-gray-900">Nada encontrado com esses filtros</p>
            <p class="mt-1 text-sm text-gray-600">Confira a busca ou mostre todos de novo.</p>
            <button type="button" @click="limparFiltros"
                :class="['mt-4 inline-flex min-h-11 items-center gap-2 rounded-lg border border-gray-300 bg-white px-5 text-sm font-medium text-gray-700 hover:bg-gray-50', foco]">
                <X class="h-4 w-4" aria-hidden="true" /> Limpar filtros
            </button>
        </div>

        <!-- Grade: um cartão por item, em colunas -->
        <ul v-else-if="visao === 'grade'" class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
            <li v-for="item in filtrados" :key="item.id"
                class="flex flex-col rounded-xl border border-gray-200 bg-white shadow-sm transition-shadow duration-150 hover:shadow-md motion-reduce:transition-none">
                <div class="flex items-start gap-3 p-4">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-cyan-50 text-cyan-700">
                        <IconeLucide :nome="item.icone" class="h-6 w-6" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <Link :href="route(rota('show'), item.id)" :class="['line-clamp-2 text-base font-semibold text-gray-900 hover:text-cyan-700', foco]">{{ item.titulo }}</Link>
                        <button type="button" @click="alternar(item)"
                            :class="[etiqueta(item.status), 'mt-1.5 min-h-8 cursor-pointer hover:brightness-95', foco]"
                            :title="item.status ? `Clique para inativar` : `Clique para ativar`"
                            :aria-label="`${item.status ? 'Ativo' : 'Inativo'}. ${item.status ? 'Inativar' : 'Ativar'} ${modulo.singular}`">
                            <component :is="item.status ? CheckCircle2 : CircleOff" class="h-3.5 w-3.5" aria-hidden="true" />
                            {{ item.status ? 'Ativo' : 'Inativo' }}
                        </button>
                    </div>
                </div>

                <div v-if="!modulo.semDetalhes" class="px-4 pb-3 text-sm text-gray-600">
                    <p v-if="item.descricao" class="line-clamp-2">{{ item.descricao }}</p>
                    <slot name="resumo" :item="item" />
                </div>

                <dl v-if="modulo.colunas?.length" class="mx-4 mb-4 grid grid-cols-2 gap-3 rounded-lg bg-gray-50 p-3">
                    <div v-for="coluna in modulo.colunas" :key="coluna.titulo" class="min-w-0">
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ coluna.titulo }}</dt>
                        <dd class="mt-0.5 truncate text-base font-semibold tabular-nums text-gray-900">{{ coluna.valor(item) }}</dd>
                    </div>
                </dl>

                <!-- Conteúdo extra do cartão (ex.: código do cupom no processo, aba do Relatório) -->
                <slot name="cartao" :item="item" />

                <div class="mt-auto grid gap-2 border-t border-gray-100 p-3" :class="modulo.pdf ? 'grid-cols-4' : 'grid-cols-3'">
                    <Link :href="route(rota('show'), item.id)" :class="acaoCartao" title="Ver detalhes"><Eye class="h-4 w-4" aria-hidden="true" /> Ver</Link>
                    <Link :href="route(rota('edit'), item.id)" :class="acaoCartao" title="Editar"><Pencil class="h-4 w-4" aria-hidden="true" /> Editar</Link>
                    <a v-if="modulo.pdf" :href="route(rota('pdf'), item.id)" target="_blank" rel="noopener" :class="acaoCartao" :title="`Gerar PDF do ${modulo.singular}`">
                        <FileDown class="h-4 w-4" aria-hidden="true" /> PDF
                    </a>
                    <button type="button" :class="[acaoCartao, 'text-red-600 dark:text-red-400']" title="Excluir" @click="pedir(item, 'excluir')">
                        <Trash2 class="h-4 w-4" aria-hidden="true" /> Excluir
                    </button>
                </div>
            </li>
        </ul>

        <template v-else>
            <!-- Celular: um cartão por item -->
            <ul class="divide-y divide-gray-100 md:hidden">
                <li v-for="item in filtrados" :key="item.id" class="space-y-3 p-4">
                    <div class="flex items-start gap-3">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-cyan-50 text-cyan-700">
                            <IconeLucide :nome="item.icone" class="h-5 w-5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <Link :href="route(rota('show'), item.id)" :class="['block text-base font-semibold text-gray-900 hover:text-cyan-700', foco]">{{ item.titulo }}</Link>
                            <template v-if="!modulo.semDetalhes">
                                <p v-if="item.descricao" class="mt-0.5 line-clamp-2 text-sm text-gray-600">{{ item.descricao }}</p>
                                <slot name="resumo" :item="item" />
                            </template>
                            <p v-if="modulo.colunas?.length" class="mt-1 text-sm text-gray-700 tabular-nums">
                                {{ modulo.colunas.map((coluna) => (coluna.texto ?? coluna.valor)(item)).join(' · ') }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span :class="etiqueta(item.status)">
                            <component :is="item.status ? CheckCircle2 : CircleOff" class="h-3.5 w-3.5" aria-hidden="true" />
                            {{ item.status ? 'Ativo' : 'Inativo' }}
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <Link :href="route(rota('show'), item.id)" :class="acaoCartao"><Eye class="h-4 w-4" aria-hidden="true" /> Ver detalhes</Link>
                        <Link :href="route(rota('edit'), item.id)" :class="acaoCartao"><Pencil class="h-4 w-4" aria-hidden="true" /> Editar</Link>
                        <button type="button" :class="acaoCartao" @click="alternar(item)">
                            <Power class="h-4 w-4" aria-hidden="true" /> {{ item.status ? 'Inativar' : 'Ativar' }}
                        </button>
                        <button type="button" :class="[acaoCartao, 'text-red-600 dark:text-red-400']" @click="pedir(item, 'excluir')">
                            <Trash2 class="h-4 w-4" aria-hidden="true" /> Excluir
                        </button>
                        <a v-if="modulo.pdf" :href="route(rota('pdf'), item.id)" target="_blank" rel="noopener" :class="[acaoCartao, 'col-span-2']">
                            <FileDown class="h-4 w-4" aria-hidden="true" /> Gerar PDF do {{ modulo.singular }}
                        </a>
                    </div>
                </li>
            </ul>

            <!-- Computador: tabela -->
            <div class="hidden overflow-x-auto md:block">
                <Table>
                    <TableHeader>
                        <TableRow class="bg-gray-50/80">
                            <TableHead :class="cabecalho">{{ maiuscula(modulo.singular) }}</TableHead>
                            <TableHead v-if="!modulo.semDetalhes" :class="cabecalho">Detalhes</TableHead>
                            <TableHead v-for="coluna in modulo.colunas ?? []" :key="coluna.titulo" :class="[cabecalho, coluna.classe]">{{ coluna.titulo }}</TableHead>
                            <TableHead :class="[cabecalho, 'text-center']">Status</TableHead>
                            <TableHead :class="[cabecalho, 'text-center']">Ações</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="item in filtrados" :key="item.id" class="hover:bg-gray-50/50">
                            <TableCell class="py-4">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-cyan-50 text-cyan-700">
                                        <IconeLucide :nome="item.icone" class="h-5 w-5" />
                                    </span>
                                    <Link :href="route(rota('show'), item.id)" :class="['font-semibold text-gray-900 hover:text-cyan-700 hover:underline', foco]">
                                        {{ item.titulo }}
                                    </Link>
                                </div>
                            </TableCell>
                            <TableCell v-if="!modulo.semDetalhes" class="max-w-md py-4 text-sm text-gray-600">
                                <span v-if="item.descricao" class="line-clamp-2">{{ item.descricao }}</span>
                                <span v-else-if="!$slots.resumo" class="text-gray-500">Sem descrição</span>
                                <slot name="resumo" :item="item" />
                            </TableCell>
                            <TableCell v-for="coluna in modulo.colunas ?? []" :key="coluna.titulo" :class="['py-4 text-sm text-gray-800', coluna.classe]">
                                {{ coluna.valor(item) }}
                            </TableCell>
                            <TableCell class="py-4 text-center">
                                <button type="button" @click="alternar(item)"
                                    :class="[etiqueta(item.status), 'min-h-9 cursor-pointer hover:brightness-95', foco]"
                                    :title="item.status ? `Clique para inativar` : `Clique para ativar`"
                                    :aria-label="`${item.status ? 'Ativo' : 'Inativo'}. ${item.status ? 'Inativar' : 'Ativar'} ${modulo.singular}`">
                                    <component :is="item.status ? CheckCircle2 : CircleOff" class="h-3.5 w-3.5" aria-hidden="true" />
                                    {{ item.status ? 'Ativo' : 'Inativo' }}
                                </button>
                            </TableCell>
                            <TableCell class="py-4 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <Link :href="route(rota('show'), item.id)" :class="acao" title="Ver detalhes" aria-label="Ver detalhes"><Eye class="h-5 w-5" /></Link>
                                    <Link :href="route(rota('edit'), item.id)" :class="acao" title="Editar" aria-label="Editar"><Pencil class="h-5 w-5" /></Link>
                                    <a v-if="modulo.pdf" :href="route(rota('pdf'), item.id)" target="_blank" rel="noopener" :class="acao"
                                        :title="`Gerar PDF do ${modulo.singular}`" :aria-label="`Gerar PDF do ${modulo.singular}`"><FileDown class="h-5 w-5" /></a>
                                    <button type="button" :class="[acao, 'hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/40']" title="Excluir" aria-label="Excluir" @click="pedir(item, 'excluir')">
                                        <Trash2 class="h-5 w-5" />
                                    </button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </template>

        <ConfirmDeleteModal :show="!!pendente" :title="acaoPendente?.titulo"
            :message="pendente ? acaoPendente.mensagem(pendente.item) : ''" :warning-message="acaoPendente?.aviso"
            :confirm-text="acaoPendente?.confirmar" cancel-text="Voltar" :variant="acaoPendente?.variante ?? 'danger'"
            :isProcessing="processando" @close="fechar" @confirm="confirmar" />
    </div>
</template>
