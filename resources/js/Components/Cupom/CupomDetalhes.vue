<script setup>
import { Link } from "@inertiajs/vue3";
import ConfirmDeleteModal from "@/Components/ConfirmDeleteModal.vue";
import CupomUsos from "@/Components/Cupom/CupomUsos.vue";
import SituacaoCupom from "@/Components/Cupom/SituacaoCupom.vue";
import { fmtDesconto, fmtMoeda } from "@/Composables/Cupom/situacao";
import { useAcoesCupom } from "@/Composables/Cupom/useAcoesCupom";
import { CalendarClock, FileDown, Pencil, Percent, Store, Trash2, Users } from "lucide-vue-next";

/**
 * Detalhe do cupom (somente leitura) com situação, PDF, edição, exclusão e usos.
 * `rotas` = { edit, pdf, status, destroy } (nomes); `mostrarParceiro` só no central.
 */
const props = defineProps({
    cupom: { type: Object, required: true },
    usos: { type: Array, default: () => [] },
    rotas: { type: Object, required: true },
    mostrarParceiro: { type: Boolean, default: false },
});

const { pendente, acaoPendente, processando, pedir, fechar, confirmar } = useAcoesCupom(props.rotas);

const card = "w-full rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6";
const botao = "inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border px-4 text-sm font-semibold";
</script>

<template>
    <div class="space-y-6">
        <!-- Cabeçalho -->
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="font-mono text-2xl font-bold tracking-wide text-gray-900 sm:text-3xl">{{ cupom.codigo }}</h1>
                    <SituacaoCupom :cupom="cupom" @pedir="(tipo) => pedir(cupom, tipo)" />
                </div>
                <p class="mt-1 text-sm text-gray-500">
                    Criado por {{ cupom.criado_por ?? '—' }} em {{ cupom.criado_em }} · atualizado em {{ cupom.atualizado_em }}
                </p>
            </div>

            <div class="grid grid-cols-3 gap-2 sm:flex sm:flex-wrap">
                <Link :href="route(rotas.edit, cupom.id)" :class="[botao, 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50']">
                    <Pencil class="h-4 w-4" aria-hidden="true" /> Editar
                </Link>
                <a :href="route(rotas.pdf, cupom.id)" target="_blank" rel="noopener" :class="[botao, 'border-cyan-600 text-cyan-700 hover:bg-cyan-50']">
                    <FileDown class="h-4 w-4" aria-hidden="true" /> PDF
                </a>
                <button type="button" :disabled="!cupom.pode_excluir" @click="pedir(cupom, 'excluir')"
                    :title="cupom.pode_excluir ? 'Excluir' : 'Cupom já usado: cancele em vez de excluir'"
                    :class="[botao, 'border-red-200 text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40']">
                    <Trash2 class="h-4 w-4" aria-hidden="true" /> Excluir
                </button>
            </div>
        </div>

        <!-- Resumo -->
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div :class="card">
                <p class="flex items-center gap-2 text-sm text-gray-500"><Percent class="h-4 w-4 text-cyan-600" aria-hidden="true" /> Desconto</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ fmtDesconto(cupom) }}</p>
                <p class="text-xs text-gray-500">{{ cupom.tipo_label }}</p>
            </div>
            <div :class="card">
                <p class="flex items-center gap-2 text-sm text-gray-500"><Users class="h-4 w-4 text-cyan-600" aria-hidden="true" /> Usos</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">
                    {{ cupom.usos }}<span v-if="cupom.limite_uso_total" class="text-base font-semibold text-gray-500"> / {{ cupom.limite_uso_total }}</span>
                </p>
                <p class="text-xs text-gray-500">{{ cupom.limite_uso_total ? 'do limite total' : 'sem limite total' }}</p>
            </div>
            <div :class="card">
                <p class="flex items-center gap-2 text-sm text-gray-500"><CalendarClock class="h-4 w-4 text-cyan-600" aria-hidden="true" /> Validade</p>
                <p class="mt-2 text-sm font-semibold text-gray-900">{{ cupom.inicio_formatado }}</p>
                <p class="text-sm font-semibold text-gray-900">até {{ cupom.expira_formatado }}</p>
            </div>
            <div :class="card">
                <p class="flex items-center gap-2 text-sm text-gray-500"><Store class="h-4 w-4 text-cyan-600" aria-hidden="true" /> Parceiro</p>
                <p class="mt-2 text-base font-semibold text-gray-900">
                    {{ mostrarParceiro ? (cupom.parceiro ?? 'Todos (global)') : 'Este parceiro' }}
                </p>
                <p class="text-xs text-gray-500">Origem: {{ cupom.origem_label }}</p>
            </div>
        </div>

        <!-- Regras -->
        <section :class="card">
            <h2 class="text-base font-semibold text-gray-900">Regras</h2>
            <dl class="mt-4 grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 xl:grid-cols-4">
                <div>
                    <dt class="text-gray-500">Onde vale</dt>
                    <dd class="font-medium text-gray-900">{{ cupom.aplicavel_label }}</dd>
                    <dd v-if="cupom.planos_nomes.length" class="text-gray-700">{{ cupom.planos_nomes.join(', ') }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Usos por CPF</dt>
                    <dd class="font-medium text-gray-900">{{ cupom.limite_uso_por_cpf }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Valor mínimo do plano</dt>
                    <dd class="font-medium text-gray-900">{{ cupom.valor_minimo > 0 ? fmtMoeda(cupom.valor_minimo) : 'Sem mínimo' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Acumulável</dt>
                    <dd class="font-medium text-gray-900">Não (um cupom por venda)</dd>
                </div>
                <div v-if="cupom.observacoes" class="sm:col-span-2 xl:col-span-4">
                    <dt class="text-gray-500">Observações</dt>
                    <dd class="whitespace-pre-line text-gray-800">{{ cupom.observacoes }}</dd>
                </div>
            </dl>
        </section>

        <CupomUsos :usos="usos" />

        <ConfirmDeleteModal :show="!!pendente" :title="acaoPendente?.titulo"
            :message="pendente ? acaoPendente.mensagem(pendente.cupom) : ''" :warning-message="acaoPendente?.aviso"
            :confirm-text="acaoPendente?.confirmar" cancel-text="Voltar" :variant="acaoPendente?.variante ?? 'danger'"
            :isProcessing="processando" @close="fechar" @confirm="confirmar" />
    </div>
</template>
