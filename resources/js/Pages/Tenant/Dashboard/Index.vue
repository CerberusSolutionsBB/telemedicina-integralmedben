<script setup>
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import TenantAdminLayout from "@/Layouts/TenantAdminLayout.vue";
import GraficoLinhaAcumulada from "@/Components/Dashboard/GraficoLinhaAcumulada.vue";
import GraficoBarrasMes from "@/Components/Dashboard/GraficoBarrasMes.vue";
import GraficoRosca from "@/Components/Dashboard/GraficoRosca.vue";
import { Heart, Layers, UserPlus, PieChart } from "lucide-vue-next";
import { ref, computed } from "vue";

const props = defineProps({
    currentYear: { type: Number, default: new Date().getFullYear() },
    currentMonth: { type: Number, default: new Date().getMonth() + 1 },
    selectedMonth: { type: Number, default: null },
    monthLabels: { type: Array, default: () => [] },
    periodoLabel: { type: String, default: '' },
    updatedAt: { type: String, default: '' },
    totalPatients: { type: Number, default: 0 },
    activePatients: { type: Number, default: 0 },
    novosNoPeriodo: { type: Number, default: 0 },
    monthlyGrowth: { type: Array, default: () => [] },
    porOrigem: { type: Array, default: () => [] },
    porUsuario: { type: Array, default: () => [] },
    porSexo: { type: Array, default: () => [] },
    porFaixaEtaria: { type: Array, default: () => [] },
    planos: { type: Array, default: () => [] },
    planosNoLimite: { type: Object, default: () => ({ total: 0, descricao: '' }) },
    comissoes: { type: Object, default: () => ({ total: 0, valor: 0, ticket: 0, vendedores: [], por_plano: [] }) },
    sms: { type: Object, default: () => ({ saldo: 0, enviados: 0, falhas: 0, pendentes: 0 }) },
});

const page = usePage();
const nomeParceiro = computed(() => page.props.tenant_public?.name || 'parceiro');

const monthNames = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
const availableYears = Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - i);
const anoAtual = new Date().getFullYear();
const PALETA = ['#22b8cf', '#e11d2e', '#1e293b', '#f59e0b', '#94a3b8', '#8b5cf6'];

const fmtReal = (v) => Number(v || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
const inativos = computed(() => props.totalPatients - props.activePatients);

// Recarrega os dados do período; enquanto a requisição roda, a tela mostra o esqueleto.
const monthValue = ref(props.selectedMonth || 0);
const carregando = ref(false);

const recarregar = (params) => {
    router.visit(route('tenant.dashboard', params), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onStart: () => { carregando.value = true; },
        onFinish: () => { carregando.value = false; },
    });
};

const goToYear = (year) => recarregar({ year, month: props.selectedMonth || undefined });
const goToMonth = (month) => {
    monthValue.value = Number(month);
    recarregar({ year: props.currentYear, month: Number(month) || undefined });
};

// Evolução e barras do ano
const ultimoMes = computed(() => props.currentYear < anoAtual ? 12 : props.currentMonth);
const acumulado = computed(() => {
    let soma = 0;
    return props.monthlyGrowth.slice(0, ultimoMes.value).map(v => (soma += v));
});
const totalAno = computed(() => props.monthlyGrowth.reduce((a, b) => a + b, 0));
// Destaca o mês filtrado; sem filtro, o mês atual (só no ano corrente).
const destaqueMes = computed(() => props.selectedMonth ? props.selectedMonth - 1 : (props.currentYear === anoAtual ? props.currentMonth - 1 : -1));

// Planos contratados
const vidasPorPlano = computed(() => props.planos.map((p, i) => ({ nome: p.plano, valor: p.em_uso, cor: PALETA[i % PALETA.length] })));
const vagasContratadas = computed(() => props.planos.reduce((a, p) => a + p.quantidade, 0));
const vagasDisponiveis = computed(() => props.planos.reduce((a, p) => a + p.disponivel, 0));
const vidasEmUso = computed(() => props.planos.reduce((a, p) => a + p.em_uso, 0));
const receita = computed(() => props.planos.reduce((a, p) => a + p.receita, 0));
const ocupacao = (p) => p.quantidade ? Math.min(100, Math.round(p.em_uso / p.quantidade * 100)) : 0;

// Perfil e origem
const maxOrigem = computed(() => Math.max(...props.porOrigem.map(o => o.total), 1));
const maxUsuario = computed(() => Math.max(...props.porUsuario.map(u => u.total), 1));
const maxFaixa = computed(() => Math.max(...props.porFaixaEtaria.map(f => f.total), 1));
const totalSexo = computed(() => props.porSexo.reduce((a, s) => a + s.total, 0));
const CORES_SEXO = ['#22b8cf', '#e11d2e', '#cbd5e1'];
</script>

<template>

    <Head title="Dashboard" />

    <TenantAdminLayout>
        <div class="py-6 space-y-5">

            <!-- Cabeçalho -->
            <section class="relative overflow-hidden rounded-2xl bg-[#23BACF] px-5 sm:px-6 pt-5 pb-5 text-white">
                <Heart class="pointer-events-none absolute -right-6 -top-6 h-36 w-36 text-white/10" />
                <div class="relative flex flex-col lg:flex-row lg:items-start justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-extrabold uppercase tracking-wide">Dashboard</h2>
                        <p class="mt-1 text-sm text-white/90">
                            Visão geral de {{ nomeParceiro }} em {{ periodoLabel }} ·
                            <span v-if="carregando"
                                class="inline-block h-3 w-28 rounded bg-white/30 align-middle animate-pulse" />
                            <template v-else>atualizado em {{ updatedAt }}</template>
                        </p>
                    </div>
                    <div class="grid grid-cols-2 sm:flex gap-2">
                        <label class="flex flex-col gap-1 text-xs text-white/90">
                            Período
                            <select :value="monthValue" :disabled="carregando" @change="goToMonth($event.target.value)"
                                class="rounded-lg border-0 bg-white px-3 py-2 pr-8 text-sm font-medium text-gray-800 focus:ring-2 focus:ring-cyan-300">
                                <option :value="0">Todos os meses</option>
                                <option v-for="(name, i) in monthNames" :key="i" :value="i + 1">{{ name }}</option>
                            </select>
                        </label>
                        <label class="flex flex-col gap-1 text-xs text-white/90">
                            Ano
                            <select :value="currentYear" :disabled="carregando" @change="goToYear($event.target.value)"
                                class="rounded-lg border-0 bg-white px-3 py-2 pr-8 text-sm font-medium text-gray-800 focus:ring-2 focus:ring-cyan-300">
                                <option v-for="y in availableYears" :key="y" :value="y">{{ y }}</option>
                            </select>
                        </label>
                    </div>
                </div>

                <div class="relative mt-5 grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <div class="rounded-xl bg-white px-4 py-3 text-gray-800">
                        <p class="text-xs font-medium text-gray-600">Beneficiários</p>
                        <div v-if="carregando" class="mt-2 h-7 w-16 rounded bg-gray-200 animate-pulse" />
                        <p v-else class="mt-1 text-2xl font-extrabold text-[#23BACF]">{{
                            totalPatients.toLocaleString('pt-BR') }}</p>
                    </div>
                    <div class="rounded-xl bg-white px-4 py-3 text-gray-800">
                        <p class="text-xs font-medium text-gray-600">Novos em {{ periodoLabel }}</p>
                        <div v-if="carregando" class="mt-2 h-7 w-16 rounded bg-gray-200 animate-pulse" />
                        <p v-else class="mt-1 text-2xl font-extrabold text-red-600">+{{
                            novosNoPeriodo.toLocaleString('pt-BR') }}</p>
                    </div>
                    <div class="rounded-xl bg-white px-4 py-3 text-gray-800">
                        <p class="text-xs font-medium text-gray-600">Beneficiários ativos</p>
                        <div v-if="carregando" class="mt-2 h-7 w-16 rounded bg-gray-200 animate-pulse" />
                        <template v-else>
                            <p class="mt-1 text-2xl font-extrabold">{{ activePatients }}<span
                                    class="ml-1 text-base font-semibold text-gray-500">/ {{ totalPatients }}</span></p>
                            <p class="mt-1 text-xs font-semibold text-red-600">{{ inativos.toLocaleString('pt-BR') }}
                                inativos</p>
                        </template>
                    </div>
                    <div class="rounded-xl bg-white px-4 py-3 text-gray-800">
                        <p class="text-xs font-medium text-gray-600">Vagas disponíveis nos planos</p>
                        <div v-if="carregando" class="mt-2 h-7 w-16 rounded bg-gray-200 animate-pulse" />
                        <p v-else class="mt-1 text-2xl font-extrabold">{{ vagasDisponiveis }}<span
                                class="ml-1 text-base font-semibold text-gray-500">/ {{ vagasContratadas }}</span></p>
                    </div>
                </div>
            </section>

            <!-- Esqueleto enquanto os dados do período carregam -->
            <div v-if="carregando" class="space-y-5" aria-busy="true" aria-label="Carregando dados do dashboard">
                <div v-for="linha in 2" :key="'sk-' + linha" class="grid grid-cols-1 xl:grid-cols-3 gap-5">
                    <div class="xl:col-span-2 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm animate-pulse">
                        <div class="h-4 w-56 rounded bg-gray-200" />
                        <div class="mt-2 h-3 w-40 rounded bg-gray-100" />
                        <div class="mt-6 flex h-44 items-end gap-3">
                            <div v-for="(h, i) in [20, 35, 25, 45, 30, 55, 70, 50, 80, 60, 40, 65]" :key="i"
                                class="flex-1 rounded-t-md bg-gray-100" :style="{ height: h + '%' }" />
                        </div>
                    </div>
                    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm animate-pulse">
                        <div class="h-4 w-40 rounded bg-gray-200" />
                        <div class="mt-5 space-y-3">
                            <div v-for="n in 4" :key="n" class="h-10 rounded-xl bg-gray-100" />
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    <div v-for="n in 3" :key="'sk-p-' + n"
                        class="h-56 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm animate-pulse">
                        <div class="h-4 w-36 rounded bg-gray-200" />
                        <div class="mt-5 space-y-3">
                            <div v-for="m in 4" :key="m" class="h-4 rounded bg-gray-100" />
                        </div>
                    </div>
                </div>
            </div>

            <template v-else>
                <!-- Comissões por venda de plano -->
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
                    <div class="xl:col-span-2 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-gray-900">Comissões por vendedor</h3>
                                <p class="text-xs text-gray-500">Vendas de plano e comissão no período</p>
                            </div>
                            <span class="shrink-0 text-sm font-semibold text-gray-700">{{ comissoes.total }}
                                venda(s)</span>
                        </div>

                        <p v-if="!comissoes.vendedores.length" class="py-8 text-center text-sm text-gray-500">Nenhuma
                            venda no período.</p>
                        <div v-else class="mt-3 overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b text-xs uppercase tracking-wide text-gray-500">
                                        <th class="py-2 text-left font-semibold">Vendedor</th>
                                        <th class="py-2 text-right font-semibold">Vendas</th>
                                        <th class="py-2 text-right font-semibold">Comissão</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="v in comissoes.vendedores" :key="v.id" class="border-b border-gray-100">
                                        <td class="py-2.5 text-gray-800">{{ v.nome }}</td>
                                        <td class="py-2.5 text-right text-gray-700">{{ v.total }}</td>
                                        <td class="py-2.5 text-right font-semibold text-gray-900">{{ fmtReal(v.valor) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-bold text-gray-900">Resumo de comissões</h3>
                        <div class="mt-4 space-y-3">
                            <div class="flex items-center justify-between rounded-xl bg-cyan-50 px-4 py-3">
                                <span class="text-sm font-medium text-gray-700">Comissão total</span>
                                <span class="text-lg font-extrabold text-gray-900">{{ fmtReal(comissoes.valor) }}</span>
                            </div>
                            <div class="flex items-center justify-between rounded-xl bg-gray-50 px-4 py-3">
                                <span class="text-sm font-medium text-gray-700">Ticket médio</span>
                                <span class="text-lg font-extrabold text-gray-900">{{ fmtReal(comissoes.ticket)
                                    }}</span>
                            </div>
                            <div v-if="comissoes.por_plano.length" class="pt-1">
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Por plano
                                </p>
                                <div v-for="p in comissoes.por_plano" :key="p.cod_plano"
                                    class="flex items-center justify-between gap-3 py-1 text-sm">
                                    <span class="truncate text-gray-700" :title="p.plano">{{ p.plano }} <span
                                            class="text-xs text-gray-400">({{ p.total }})</span></span>
                                    <span class="shrink-0 font-semibold text-gray-900">{{ fmtReal(p.valor) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Evolução + Vidas por plano -->
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
                    <div class="xl:col-span-2 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-bold text-gray-900">Evolução da base de beneficiários</h3>
                        <p class="text-xs text-gray-500">Total acumulado de cadastros em {{ currentYear }}</p>
                        <GraficoLinhaAcumulada class="mt-4" :valores="acumulado" :labels="monthLabels" />
                    </div>

                    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-bold text-gray-900">Vidas por plano</h3>
                        <p class="text-xs text-gray-500">Vagas em uso em cada plano contratado</p>
                        <GraficoRosca :itens="vidasPorPlano" rotulo="vidas" vazio="Nenhuma vida nos planos ainda." />
                    </div>
                </div>

                <!-- Novos por mês + Atenção -->
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
                    <div class="xl:col-span-2 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-gray-900">Novos beneficiários por mês</h3>
                                <p class="text-xs text-gray-500">Total no ano: {{ totalAno }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-3 text-xs text-gray-600">
                                <span class="inline-flex items-center gap-1.5"><span
                                        class="h-2.5 w-2.5 rounded-sm bg-[#22b8cf]" /> Meses anteriores</span>
                                <span class="inline-flex items-center gap-1.5"><span
                                        class="h-2.5 w-2.5 rounded-sm bg-red-600" /> Mês atual</span>
                            </div>
                        </div>
                        <GraficoBarrasMes class="mt-4" :valores="monthlyGrowth" :labels="monthLabels"
                            :destaque="destaqueMes" />
                    </div>

                    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-bold text-gray-900">Atenção</h3>
                        <div class="mt-4 space-y-2.5">
                            <Link :href="route('patients.index')"
                                class="flex items-center justify-between gap-3 rounded-xl bg-red-50 px-3 py-2.5 hover:bg-red-100 transition-colors">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900">Beneficiários inativos</p>
                                    <p class="text-xs text-gray-500">Ver na lista de beneficiários</p>
                                </div>
                                <span class="text-lg font-extrabold text-gray-900">{{ totalPatients - activePatients
                                    }}</span>
                            </Link>
                            <div class="flex items-center justify-between gap-3 rounded-xl bg-amber-50 px-3 py-2.5">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900">Planos perto do limite</p>
                                    <p class="truncate text-xs text-gray-500" :title="planosNoLimite.descricao">
                                        {{ planosNoLimite.descricao }}
                                    </p>
                                </div>
                                <span class="text-lg font-extrabold text-gray-900">{{ planosNoLimite.total }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3 rounded-xl bg-cyan-50 px-3 py-2.5">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900">Falhas de envio de SMS</p>
                                    <p class="text-xs text-gray-500">{{ sms.enviados }} enviados em {{ periodoLabel }}
                                    </p>
                                </div>
                                <span class="text-lg font-extrabold text-gray-900">{{ sms.falhas }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3 rounded-xl bg-gray-100 px-3 py-2.5">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900">Saldo de SMS</p>
                                    <p class="text-xs text-gray-500">Mensagens disponíveis para envio</p>
                                </div>
                                <span class="text-lg font-extrabold"
                                    :class="sms.saldo > 0 ? 'text-gray-900' : 'text-red-600'">{{ sms.saldo }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Origem, usuários e perfil (respeitam o período) -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <div class="flex items-center gap-2">
                            <Layers class="h-4 w-4 text-[#23BACF]" />
                            <h3 class="text-base font-bold text-gray-900">Origem do cadastro</h3>
                        </div>
                        <p class="text-xs text-gray-500">{{ periodoLabel }}</p>
                        <ul class="mt-4 space-y-3">
                            <li v-for="o in porOrigem" :key="o.label">
                                <div class="flex justify-between text-sm">
                                    <span class="truncate text-gray-700">{{ o.label }}</span>
                                    <span class="font-bold text-gray-900">{{ o.total }}</span>
                                </div>
                                <div class="mt-1 h-2 rounded-full bg-gray-100">
                                    <div class="h-2 rounded-full bg-[#22b8cf]"
                                        :style="{ width: (o.total / maxOrigem * 100) + '%' }" />
                                </div>
                            </li>
                            <li v-if="!porOrigem.length" class="py-6 text-center text-xs text-gray-500">Nenhum cadastro
                                no período.</li>
                        </ul>
                    </div>

                    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <div class="flex items-center gap-2">
                            <UserPlus class="h-4 w-4 text-[#23BACF]" />
                            <h3 class="text-base font-bold text-gray-900">Quem mais cadastrou</h3>
                        </div>
                        <p class="text-xs text-gray-500">{{ periodoLabel }}</p>
                        <ul class="mt-4 space-y-3">
                            <li v-for="(u, i) in porUsuario" :key="u.nome" class="flex items-center gap-3">
                                <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full text-xs font-bold"
                                    :class="i < 3 ? 'text-white' : 'bg-gray-100 text-gray-500'"
                                    :style="i < 3 ? { background: PALETA[i] } : {}">{{ i + 1 }}</span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex justify-between text-sm">
                                        <span class="truncate text-gray-700" :title="u.nome">{{ u.nome }}</span>
                                        <span class="font-bold text-gray-900">{{ u.total }}</span>
                                    </div>
                                    <div class="mt-1 h-1.5 rounded-full bg-gray-100">
                                        <div class="h-1.5 rounded-full"
                                            :style="{ width: (u.total / maxUsuario * 100) + '%', background: PALETA[i] || '#94a3b8' }" />
                                    </div>
                                </div>
                            </li>
                            <li v-if="!porUsuario.length" class="py-6 text-center text-xs text-gray-500">Nenhum cadastro
                                feito por usuários no período.</li>
                        </ul>
                    </div>

                    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <div class="flex items-center gap-2">
                            <PieChart class="h-4 w-4 text-[#23BACF]" />
                            <h3 class="text-base font-bold text-gray-900">Perfil dos beneficiários</h3>
                        </div>
                        <p class="text-xs text-gray-500">{{ periodoLabel }}</p>

                        <div class="mt-4 flex h-3 overflow-hidden rounded-full bg-gray-100">
                            <div v-for="(s, i) in porSexo" :key="s.label" :title="`${s.label}: ${s.total}`"
                                :style="{ width: totalSexo ? (s.total / totalSexo * 100) + '%' : '0%', background: CORES_SEXO[i] }" />
                        </div>
                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-600">
                            <span v-for="(s, i) in porSexo" :key="s.label" class="inline-flex items-center gap-1.5">
                                <span class="h-2.5 w-2.5 rounded-sm" :style="{ background: CORES_SEXO[i] }" /> {{
                                s.label }} <strong class="text-gray-900">{{ s.total }}</strong>
                            </span>
                        </div>

                        <p class="mt-5 text-xs font-semibold uppercase tracking-wide text-gray-500">Faixa etária</p>
                        <div class="mt-2 flex h-24 items-end gap-2 border-b border-gray-200">
                            <div v-for="f in porFaixaEtaria" :key="f.label"
                                class="flex h-full flex-1 flex-col items-center justify-end"
                                :title="`${f.label}: ${f.total}`">
                                <span v-if="f.total > 0" class="mb-0.5 text-xs font-bold text-gray-900">{{ f.total
                                    }}</span>
                                <div class="w-full max-w-[2rem] rounded-t bg-[#23BACF]"
                                    :style="{ height: f.total > 0 ? (f.total / maxFaixa * 80) + '%' : '2px', opacity: f.total > 0 ? 1 : 0.25 }" />
                            </div>
                        </div>
                        <div class="mt-1 flex gap-2">
                            <span v-for="f in porFaixaEtaria" :key="f.label"
                                class="flex-1 text-center text-xs text-gray-500">{{ f.label }}</span>
                        </div>
                    </div>
                </div>

                <!-- Planos contratados -->
                <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-2">
                        <div>
                            <h3 class="text-base font-bold text-gray-900">Planos contratados</h3>
                            <p class="text-xs text-gray-500">Ocupação das vagas e receita mensal de cada plano</p>
                        </div>
                        <span class="text-sm text-gray-600">Receita mensal: <strong class="text-gray-900">{{
                                fmtReal(receita) }}</strong></span>
                    </div>
                    <div class="mt-3 overflow-x-auto">
                        <table class="w-full min-w-[560px] text-sm">
                            <thead>
                                <tr class="border-b text-xs uppercase tracking-wide text-gray-500">
                                    <th class="py-2.5 pl-2 text-left font-semibold">Plano</th>
                                    <th class="py-2.5 text-left font-semibold">Ocupação</th>
                                    <th class="py-2.5 text-right font-semibold">Valor</th>
                                    <th class="py-2.5 pr-2 text-right font-semibold">Receita/mês</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(p, i) in planos" :key="p.cod_plano" class="border-b border-gray-100">
                                    <td class="py-3 pl-2">
                                        <span class="flex items-center gap-2">
                                            <span class="h-2.5 w-2.5 shrink-0 rounded-sm"
                                                :style="{ background: PALETA[i % PALETA.length] }" />
                                            <span class="font-medium text-gray-800">{{ p.plano }}</span>
                                            <span v-if="p.quantidade && p.disponivel === 0"
                                                class="rounded-full bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-600">esgotado</span>
                                        </span>
                                    </td>
                                    <td class="py-3 pr-6">
                                        <div class="flex items-center gap-3">
                                            <div class="h-2 w-full max-w-[12rem] rounded-full bg-gray-100">
                                                <div class="h-2 rounded-full"
                                                    :class="ocupacao(p) >= 90 ? 'bg-red-600' : 'bg-[#22b8cf]'"
                                                    :style="{ width: ocupacao(p) + '%' }" />
                                            </div>
                                            <span class="whitespace-nowrap text-gray-700"><strong
                                                    class="text-gray-900">{{ p.em_uso }}</strong> / {{ p.quantidade
                                                }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3 text-right text-gray-700">{{ p.valor !== null ? fmtReal(p.valor) :
                                        '—' }}</td>
                                    <td class="py-3 pr-2 text-right font-semibold text-gray-900">{{ fmtReal(p.receita)
                                        }}</td>
                                </tr>
                                <tr v-if="!planos.length">
                                    <td colspan="4" class="py-10 text-center text-sm text-gray-500">Nenhum plano
                                        contratado.</td>
                                </tr>
                            </tbody>
                            <tfoot v-if="planos.length">
                                <tr class="font-bold text-gray-900">
                                    <td class="py-3 pl-2">Total</td>
                                    <td class="py-3"><span class="text-gray-900">{{ vidasEmUso }}</span> <span
                                            class="font-normal text-gray-500">/ {{ vagasContratadas }} vagas</span></td>
                                    <td />
                                    <td class="py-3 pr-2 text-right">{{ fmtReal(receita) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </template>

        </div>
    </TenantAdminLayout>
</template>
