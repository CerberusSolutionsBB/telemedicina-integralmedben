<script setup>
import { computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import TenantAdminLayout from "@/Layouts/TenantAdminLayout.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import ConfirmDeleteModal from "@/Components/ConfirmDeleteModal.vue";
import IconeLucide from "@/Components/Icone/IconeLucide.vue";
import HistoricoAuditoria from "@/Components/CadastroIcone/HistoricoAuditoria.vue";
import { useAcoesCadastroIcone } from "@/Composables/CadastroIcone/useAcoesCadastroIcone";
import { useAbaAtiva } from "@/Composables/Pagina/useAbaAtiva";
import { rotaDe } from "@/Composables/CadastroIcone/modulos";
import { CheckCircle2, CircleOff, FileDown, History, Home, LayoutList, Pencil, Power, Trash2 } from "lucide-vue-next";

/**
 * Tela de detalhe de um cadastro com ícone, com o histórico da auditoria.
 * Com `abas`, o conteúdo é dividido em abas (como na página do parceiro):
 * Visão geral (slot detalhes + descrição) | abas do módulo (slot aba-<key>) | Histórico.
 */
const props = defineProps({
    modulo: { type: Object, required: true },
    breadcrumbs: { type: Array, required: true },
    item: { type: Object, required: true },
    historico: { type: Array, default: () => [] },
    // Abas próprias do módulo entre "Visão geral" e "Histórico": [{ key, label, icon, contador? }].
    // null = sem abas (tudo numa página só).
    abas: { type: Array, default: null },
});

const todasAbas = computed(() => [
    { key: "geral", label: "Visão geral", icon: LayoutList },
    ...(props.abas ?? []),
    { key: "historico", label: "Histórico", icon: History, contador: props.historico.length },
]);

// Aba aberta: ?aba= na URL e sessionStorage, por item.
const { activeTab: aba } = useAbaAtiva(`${props.modulo.rota}:${props.item.id}:aba`, todasAbas.value.map((a) => a.key), "geral");

const { pendente, acaoPendente, processando, pedir, fechar, confirmar } = useAcoesCadastroIcone(props.modulo);

const breadcrumbItems = computed(() => [
    { label: "Início", href: route("patients.index"), icon: Home },
    ...props.breadcrumbs,
]);

const botao = "inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border px-4 text-sm font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 focus-visible:ring-offset-2";
</script>

<template>
    <Head :title="item.titulo" />

    <TenantAdminLayout>
        <Breadcrumb :items="breadcrumbItems" />

        <div class="space-y-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex items-start gap-4">
                    <span class="grid h-14 w-14 shrink-0 place-items-center rounded-xl bg-cyan-50 text-cyan-700">
                        <IconeLucide :nome="item.icone" class="h-7 w-7" />
                    </span>
                    <div class="min-w-0">
                        <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">{{ item.titulo }}</h1>
                        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-600">
                            <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-semibold"
                                :class="item.status
                                    ? 'border-green-200 bg-green-50 text-green-700 dark:border-green-900 dark:bg-green-950/40 dark:text-green-300'
                                    : 'border-gray-300 bg-gray-100 text-gray-700'">
                                <component :is="item.status ? CheckCircle2 : CircleOff" class="h-3.5 w-3.5" aria-hidden="true" />
                                {{ item.status ? 'Ativo: disponível' : 'Inativo: indisponível' }}
                            </span>
                            <span>Atualizado em {{ item.atualizado_em }}</span>
                        </div>
                    </div>
                </div>

                <!-- Editar é a ação principal; inativar e excluir pedem confirmação -->
                <div class="grid grid-cols-1 gap-2 sm:flex sm:flex-wrap sm:justify-end">
                    <Link :href="route(rotaDe(modulo, 'edit'), item.id)"
                        :class="[botao, 'border-cyan-600 bg-cyan-600 text-white hover:bg-cyan-700']">
                        <Pencil class="h-4 w-4" aria-hidden="true" /> Editar {{ modulo.singular }}
                    </Link>
                    <a v-if="modulo.pdf" :href="route(rotaDe(modulo, 'pdf'), item.id)" target="_blank" rel="noopener"
                        :class="[botao, 'border-cyan-600 bg-white text-cyan-700 hover:bg-cyan-50']">
                        <FileDown class="h-4 w-4" aria-hidden="true" /> Gerar PDF
                    </a>
                    <div class="grid grid-cols-2 gap-2 sm:contents">
                        <button type="button" :class="[botao, 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50']"
                            @click="pedir(item, item.status ? 'inativar' : 'ativar')">
                            <Power class="h-4 w-4" aria-hidden="true" /> {{ item.status ? 'Inativar' : 'Ativar' }}
                        </button>
                        <button type="button" :class="[botao, 'border-red-200 bg-white text-red-600 hover:bg-red-50 dark:border-red-900 dark:text-red-400 dark:hover:bg-red-950/40']"
                            @click="pedir(item, 'excluir')">
                            <Trash2 class="h-4 w-4" aria-hidden="true" /> Excluir
                        </button>
                    </div>
                </div>
            </div>

            <!-- Com abas: Visão geral | abas do módulo | Histórico -->
            <template v-if="abas">
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="overflow-x-auto border-b border-gray-100 bg-gray-50/70 px-4 pt-4 md:px-6">
                        <nav class="flex min-w-max gap-2 pb-3" aria-label="Seções">
                            <button v-for="item_aba in todasAbas" :key="item_aba.key" type="button" @click="aba = item_aba.key"
                                :aria-current="aba === item_aba.key ? 'page' : undefined"
                                class="group flex min-h-11 items-center gap-2 whitespace-nowrap rounded-xl border px-4 text-sm font-medium transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 motion-reduce:transition-none"
                                :class="aba === item_aba.key
                                    ? 'border-cyan-200 bg-white text-cyan-600 shadow-sm'
                                    : 'border-transparent bg-transparent text-gray-600 hover:border-gray-200 hover:bg-white hover:text-gray-900'">
                                <component :is="item_aba.icon" class="h-4 w-4 shrink-0" aria-hidden="true"
                                    :class="aba === item_aba.key ? 'text-cyan-500' : 'text-gray-400 group-hover:text-gray-600'" />
                                {{ item_aba.label }}
                                <span v-if="item_aba.contador !== undefined" class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ item_aba.contador }}</span>
                            </button>
                        </nav>
                    </div>

                    <div class="space-y-6 p-4 md:p-6">
                        <template v-if="aba === 'geral'">
                            <slot name="detalhes" :item="item" />
                            <section class="w-full rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                                <h2 class="text-base font-semibold text-gray-900">Descrição</h2>
                                <p class="mt-2 max-w-prose whitespace-pre-line text-base leading-relaxed text-gray-700">{{ item.descricao || 'Sem descrição.' }}</p>
                            </section>
                        </template>
                        <HistoricoAuditoria v-else-if="aba === 'historico'" :historico="historico" />
                        <template v-for="extra in abas" :key="extra.key">
                            <slot v-if="aba === extra.key" :name="`aba-${extra.key}`" :item="item" />
                        </template>
                    </div>
                </div>
            </template>

            <template v-else>
                <!-- Informações próprias do módulo (ex.: desconto, vínculos e prazo do cupom) -->
                <slot name="detalhes" :item="item" />

                <section class="w-full rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                    <h2 class="text-base font-semibold text-gray-900">Descrição</h2>
                    <p class="mt-2 max-w-prose whitespace-pre-line text-base leading-relaxed text-gray-700">{{ item.descricao || 'Sem descrição.' }}</p>
                </section>

                <HistoricoAuditoria :historico="historico" />
            </template>
        </div>

        <ConfirmDeleteModal :show="!!pendente" :title="acaoPendente?.titulo"
            :message="pendente ? acaoPendente.mensagem(pendente.item) : ''" :warning-message="acaoPendente?.aviso"
            :confirm-text="acaoPendente?.confirmar" cancel-text="Voltar" :variant="acaoPendente?.variante ?? 'danger'"
            :isProcessing="processando" @close="fechar" @confirm="confirmar" />
    </TenantAdminLayout>
</template>
