<script setup>
import { computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import TenantAdminLayout from "@/Layouts/TenantAdminLayout.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import { BARRA_CLASSES, STATUS_CLASSES } from "@/Composables/Desempenho/status";
import { Check, Home, Pencil } from "lucide-vue-next";

const props = defineProps({
    breadcrumbs: { type: Array, required: true },
    desempenho: { type: Object, required: true },
    progresso: { type: Object, required: true },
    statusLabels: { type: Object, default: () => ({}) },
});

const coletiva = computed(() => props.desempenho.tipo_meta === "coletiva");

const breadcrumbItems = computed(() => [
    { label: "Início", href: route("patients.index"), icon: Home },
    ...props.breadcrumbs,
]);

// Participação no total de registros (contribuição do usuário ou peso do plano).
const percentualDoTotal = (total) => (props.progresso.total ? Math.round((total / props.progresso.total) * 100) : 0);
const contribuicao = percentualDoTotal;
</script>

<template>
    <Head :title="desempenho.titulo" />

    <TenantAdminLayout>
        <Breadcrumb :items="breadcrumbItems" />

        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">{{ desempenho.titulo }}</h1>
                    <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="STATUS_CLASSES[progresso.status]">
                        {{ statusLabels[progresso.status] }}
                    </span>
                </div>
                <p v-if="desempenho.descricao" class="mt-1 text-sm text-gray-500">{{ desempenho.descricao }}</p>
            </div>
            <Link :href="route('desempenho.edit', desempenho.id)"
                class="inline-flex h-10 items-center gap-2 rounded-lg border-2 border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                <Pencil class="h-4 w-4" />
                Editar
            </Link>
        </div>

        <div class="space-y-6">
            <!-- Configuração -->
            <section class="grid grid-cols-2 gap-4 rounded-xl border border-gray-200 bg-white p-4 text-sm shadow-sm sm:p-6 lg:grid-cols-6">
                <div><p class="text-xs text-gray-500">Função</p><p class="font-medium text-gray-900">{{ desempenho.funcao }}</p></div>
                <div><p class="text-xs text-gray-500">Planos</p><p class="font-medium text-gray-900">{{ desempenho.plano }}</p></div>
                <div><p class="text-xs text-gray-500">Tipo</p><p class="font-medium text-gray-900">{{ desempenho.tipo_label }}</p></div>
                <div><p class="text-xs text-gray-500">Meta</p><p class="font-medium text-gray-900">{{ desempenho.meta }} {{ coletiva ? 'no grupo' : 'por usuário' }}</p></div>
                <div><p class="text-xs text-gray-500">Período</p><p class="font-medium text-gray-900">{{ desempenho.data_inicio }} a {{ desempenho.prazo }}</p></div>
                <div><p class="text-xs text-gray-500">Perfis</p><p class="font-medium text-gray-900">{{ desempenho.roles.join(', ') }}</p></div>
            </section>

            <!-- Progresso -->
            <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                <div class="flex flex-wrap items-end justify-between gap-2">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Progresso</h2>
                        <p v-if="coletiva" class="text-sm text-gray-600">
                            <strong class="text-gray-900">{{ progresso.total }}</strong> de {{ desempenho.meta }} registros do grupo
                        </p>
                        <p v-else class="text-sm text-gray-600">
                            <strong class="text-gray-900">{{ progresso.atingiram }}</strong> de {{ progresso.participantes }} usuário(s)
                            bateram a meta de {{ desempenho.meta }} · {{ progresso.total }} registros no total
                        </p>
                    </div>
                    <span class="text-2xl font-bold text-gray-900">{{ progresso.percentual }}%</span>
                </div>
                <div class="mt-3 h-3 overflow-hidden rounded-full bg-gray-100">
                    <div class="h-full rounded-full" :class="BARRA_CLASSES[progresso.status]" :style="{ width: `${progresso.percentual}%` }" />
                </div>
            </section>

            <!-- Registros de pacientes por plano -->
            <section>
                <h2 class="mb-3 text-base font-semibold text-gray-900">Registros por plano</h2>
                <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <div class="rounded-xl border border-cyan-200 bg-cyan-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Total de pacientes</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">{{ progresso.total }}</p>
                        <p class="text-xs text-gray-500">{{ progresso.por_plano.length > 1 ? 'todos os planos' : 'no plano da meta' }}</p>
                    </div>
                    <div v-for="card in progresso.por_plano" :key="card.cod_plano" class="rounded-xl border border-gray-200 bg-white p-4">
                        <p class="truncate text-xs font-medium uppercase tracking-wide text-gray-500" :title="card.plano">{{ card.plano }}</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">{{ card.total }}</p>
                        <p class="text-xs text-gray-500">{{ percentualDoTotal(card.total) }}% do total</p>
                    </div>
                </div>
            </section>

            <!-- Por usuário -->
            <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="p-4 sm:px-6">
                    <h2 class="text-base font-semibold text-gray-900">Por usuário</h2>
                    <p class="text-sm text-gray-500">Registros de beneficiários feitos por cada usuário no período.</p>
                </div>

                <p v-if="!progresso.usuarios.length" class="border-t border-gray-100 p-6 text-center text-sm text-gray-500">
                    Nenhum usuário nos perfis desta meta.
                </p>

                <div v-else class="overflow-x-auto border-t border-gray-100">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3 sm:px-6">Usuário</th>
                                <th class="px-4 py-3 text-right">Registros</th>
                                <th class="w-1/3 px-4 py-3 sm:px-6">{{ coletiva ? 'Contribuição' : 'Progresso' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="usuario in progresso.usuarios" :key="usuario.id">
                                <td class="px-4 py-3 sm:px-6">
                                    <p class="font-medium text-gray-900">{{ usuario.nome }}</p>
                                    <p class="text-xs text-gray-500">{{ usuario.email }}</p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-gray-900">
                                    {{ usuario.total }}<span v-if="!coletiva" class="font-normal text-gray-500"> / {{ desempenho.meta }}</span>
                                </td>
                                <td class="px-4 py-3 sm:px-6">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100">
                                            <div class="h-full rounded-full" :class="usuario.atingiu ? 'bg-emerald-500' : 'bg-cyan-500'"
                                                :style="{ width: `${coletiva ? contribuicao(usuario.total) : usuario.percentual}%` }" />
                                        </div>
                                        <span class="w-12 text-right text-xs text-gray-600">{{ coletiva ? contribuicao(usuario.total) : usuario.percentual }}%</span>
                                        <Check v-if="usuario.atingiu" class="h-4 w-4 text-emerald-600" aria-label="Meta batida" />
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </TenantAdminLayout>
</template>
