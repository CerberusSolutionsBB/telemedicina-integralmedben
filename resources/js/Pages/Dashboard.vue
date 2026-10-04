<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import CentralAdminLayout from "@/Layouts/CentralAdminLayout.vue";
import {
  DollarSign, Heart, TrendingUp, LogOut, AlertCircle,
  Video, Activity, PieChart, Clock, Star, Gift,
} from "lucide-vue-next";
import { ref, computed } from "vue";
import GraficoLinhaAcumulada from "@/Components/Dashboard/GraficoLinhaAcumulada.vue";
import GraficoBarrasMes from "@/Components/Dashboard/GraficoBarrasMes.vue";
import GraficoRosca from "@/Components/Dashboard/GraficoRosca.vue";

const props = defineProps({
  totalTenants:            { type: Number, default: 0 },
  activeTenants:           { type: Number, default: 0 },
  totalPatients:           { type: Number, default: 0 },
  patientsWithPlan:        { type: Number, default: 0 },
  newThisMonth:            { type: Number, default: 0 },
  monthlyGrowth:           { type: Array,  default: () => [] },
  currentYear:             { type: Number, default: new Date().getFullYear() },
  currentMonth:            { type: Number, default: new Date().getMonth() + 1 },
  selectedMonth:           { type: Number, default: null },
  pages:                   { type: Array,  default: () => [] },
  topPages:                { type: Array,  default: () => [] },
  tenantMonthlyGrowth:     { type: Object, default: () => ({}) },
  monthLabels:             { type: Array,  default: () => [] },
  planosParceiros:         { type: Array,  default: () => [] },
  planoBeneficios:         { type: Object, default: () => ({ nome: 'Plano de Benefícios', parceiros: [] }) },
  smsFailed:               { type: Number, default: null },
  updatedAt:               { type: String, default: '' },
});

const monthNames = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
const availableYears = Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - i);
const PALETA = ['#22b8cf', '#e11d2e', '#1e293b', '#f59e0b', '#94a3b8'];

// Cor indicativa definida na Página de Parceiros; sem ela, cor padrão pela posição.
const corDaPagina = (pagina, i) => pagina?.cor || PALETA[i % PALETA.length];

const monthValue = ref(props.selectedMonth || 0);
const selectedPage = ref(null);
const selectedPlano = ref('');

// Recarrega os dados do período; enquanto a requisição roda, a tela mostra o esqueleto.
const carregando = ref(false);

const recarregar = (params) => {
  router.visit(route('dashboard', params), {
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

const nomeMesAtual = monthNames[props.currentMonth - 1].toLowerCase();
const anoAtual = new Date().getFullYear();
const ultimoMes = computed(() => props.currentYear < anoAtual ? 12 : props.currentMonth);

const mensal = computed(() => {
  if (!selectedPage.value) return props.monthlyGrowth.map(d => d.value);
  const dados = props.tenantMonthlyGrowth[selectedPage.value] || [];
  return props.monthLabels.map((_, i) => Number(dados[i] || 0));
});

const totalAno = computed(() => mensal.value.reduce((a, b) => a + b, 0));

const cadastrosAnoPorPagina = (id) => (props.tenantMonthlyGrowth[id] || []).reduce((a, b) => a + Number(b), 0);
const paginasSemCadastro = computed(() => props.pages.filter(p => cadastrosAnoPorPagina(p.id) === 0));

// Evolução acumulada (linha)
const acumulado = computed(() => {
  let soma = 0;
  return mensal.value.slice(0, ultimoMes.value).map(v => (soma += v));
});

const crescimentoMes = computed(() => {
  const a = acumulado.value;
  const atual = a[a.length - 1] || 0;
  const anterior = a[a.length - 2] || 0;
  if (!anterior || atual === anterior) return null;
  return Math.round((atual - anterior) / anterior * 100);
});

// Mês atual em destaque nas barras (só no ano corrente).
const destaqueMes = computed(() => props.currentYear === anoAtual ? props.currentMonth - 1 : -1);

// Rosca por parceiro
const fatias = computed(() => props.pages
  .map(p => ({ ...p, ano: cadastrosAnoPorPagina(p.id) }))
  .filter(p => p.ano > 0)
  .sort((a, b) => b.ano - a.ano)
  .map((p, i) => ({ nome: p.name, valor: p.ano, cor: corDaPagina(p, i) })));

// Planos das Páginas de Parceiros: vidas em uso × valor do plano.
const fmtReal = (v) => Number(v || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

const opcoesPlano = computed(() => {
  const mapa = new Map(props.planosParceiros.map(p => [p.cod_plano, p.plano]));
  return [...mapa].map(([value, label]) => ({ value, label }));
});

const planosDoPlano = computed(() =>
  props.planosParceiros.filter(p => !selectedPlano.value || p.cod_plano === selectedPlano.value));

const planosFiltrados = computed(() =>
  planosDoPlano.value.filter(p => !selectedPage.value || p.tenant_id === selectedPage.value));

const vidasAtivas = computed(() => planosFiltrados.value.reduce((a, p) => a + p.em_uso, 0));
const mrr = computed(() => planosFiltrados.value.reduce((a, p) => a + p.em_uso * (p.valor || 0), 0));
const ticketMedio = computed(() => vidasAtivas.value ? mrr.value / vidasAtivas.value : null);

const receitaDaPagina = (id) => planosDoPlano.value.filter(p => p.tenant_id === id).reduce((a, p) => a + p.em_uso * (p.valor || 0), 0);
const vidasDaPagina = (id) => planosDoPlano.value.filter(p => p.tenant_id === id).reduce((a, p) => a + p.em_uso, 0);

const CORES_PLANO = ['bg-cyan-50 border-cyan-100', 'bg-red-50 border-red-100', 'bg-gray-100 border-gray-200'];
const vidasPorPlano = computed(() => {
  const grupos = new Map();
  for (const p of planosFiltrados.value) {
    const g = grupos.get(p.cod_plano) || { cod_plano: p.cod_plano, plano: p.plano, vidas: 0, receita: 0, valores: new Set() };
    g.vidas += p.em_uso;
    g.receita += p.em_uso * (p.valor || 0);
    if (p.valor !== null) g.valores.add(p.valor);
    grupos.set(p.cod_plano, g);
  }
  return [...grupos.values()].sort((a, b) => b.receita - a.receita || b.vidas - a.vidas);
});

const mensalidade = (g) => {
  const valores = [...g.valores].sort((a, b) => a - b);
  if (!valores.length) return 'não definida';
  if (valores.length === 1) return fmtReal(valores[0]);
  return `${fmtReal(valores[0])} a ${fmtReal(valores[valores.length - 1])}`;
};

// Plano de Benefícios (plano interno): respeita o filtro de parceiro.
const nomePagina = (id) => props.pages.find(p => p.id === id)?.name ?? id;

const beneficios = computed(() => {
  const parceiros = props.planoBeneficios.parceiros
    .filter(p => !selectedPage.value || p.tenant_id === selectedPage.value)
    .map(p => ({ ...p, nome: nomePagina(p.tenant_id), cor: props.pages.find(pg => pg.id === p.tenant_id)?.cor, receita: p.beneficiarios * (p.valor || 0) }))
    .sort((a, b) => b.beneficiarios - a.beneficiarios || b.receita - a.receita);

  const soma = (campo) => parceiros.reduce((a, p) => a + p[campo], 0);
  const porMes = Array.from({ length: 12 }, (_, i) => parceiros.reduce((a, p) => a + (p.novos_por_mes[i] || 0), 0));

  return {
    parceiros,
    beneficiarios: soma('beneficiarios'),
    contratadas: soma('quantidade'),
    disponiveis: soma('disponivel'),
    receita: soma('receita'),
    porMes,
    novosAno: porMes.reduce((a, b) => a + b, 0),
    novosMes: porMes[props.currentMonth - 1] || 0,
  };
});
const beneficiosMaxMes = computed(() => Math.max(...beneficios.value.porMes, 1));
const ocupacao = (p) => p.quantidade ? Math.min(100, Math.round(p.beneficiarios / p.quantidade * 100)) : 0;

// Desempenho: receita dos planos, empate por cadastros.
const desempenho = computed(() => props.pages
  .map(p => ({ ...p, receita: receitaDaPagina(p.id), vidas: vidasDaPagina(p.id) }))
  .sort((a, b) => b.receita - a.receita || b.patients - a.patients)
  .slice(0, 5));
const desempenhoMax = computed(() => Math.max(...desempenho.value.map(p => p.patients), 1));
const desempenhoTotal = computed(() => desempenho.value.reduce((a, p) => a + p.patients, 0));
const desempenhoVidas = computed(() => desempenho.value.reduce((a, p) => a + p.vidas, 0));
const desempenhoReceita = computed(() => desempenho.value.reduce((a, p) => a + p.receita, 0));
</script>

<template>
  <Head title="Dashboard" />

  <CentralAdminLayout>
    <div class="py-6 space-y-5">

      <!-- Cabeçalho -->
      <section class="relative overflow-hidden rounded-2xl bg-[#0f7a85] px-5 sm:px-6 pt-5 pb-5 text-white">
        <Heart class="pointer-events-none absolute -right-6 -top-6 h-36 w-36 text-white/10" />
        <div class="relative flex flex-col lg:flex-row lg:items-start justify-between gap-4">
          <div>
            <h2 class="text-2xl font-extrabold uppercase tracking-wide">Dashboard</h2>
            <p class="mt-1 text-sm text-white/80">
              Visão geral de {{ currentYear }} ·
              <span v-if="carregando" class="inline-block h-3 w-28 rounded bg-white/30 align-middle animate-pulse" />
              <template v-else>atualizado em {{ updatedAt }}</template>
            </p>
          </div>
          <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-2">
            <label class="flex flex-col gap-1 text-xs text-white/80">
              Período
              <select :value="monthValue" :disabled="carregando" @change="goToMonth($event.target.value)"
                class="rounded-lg border-0 bg-white px-3 py-2 pr-8 text-sm font-medium text-gray-800 focus:ring-2 focus:ring-cyan-300">
                <option :value="0">Todos os meses</option>
                <option v-for="(name, i) in monthNames" :key="i" :value="i + 1">{{ name }}</option>
              </select>
            </label>
            <label class="flex flex-col gap-1 text-xs text-white/80">
              Ano
              <select :value="currentYear" :disabled="carregando" @change="goToYear($event.target.value)"
                class="rounded-lg border-0 bg-white px-3 py-2 pr-8 text-sm font-medium text-gray-800 focus:ring-2 focus:ring-cyan-300">
                <option v-for="y in availableYears" :key="y" :value="y">{{ y }}</option>
              </select>
            </label>
            <label class="flex flex-col gap-1 text-xs text-white/80">
              Parceiro
              <select v-model="selectedPage"
                class="rounded-lg border-0 bg-white px-3 py-2 pr-8 text-sm font-medium text-gray-800 focus:ring-2 focus:ring-cyan-300">
                <option :value="null">Todos os parceiros</option>
                <option v-for="page in pages" :key="page.id" :value="page.id">{{ page.name }}</option>
              </select>
            </label>
            <label class="flex flex-col gap-1 text-xs text-white/80">
              Plano
              <select v-model="selectedPlano"
                class="rounded-lg border-0 bg-white px-3 py-2 pr-8 text-sm font-medium text-gray-800 focus:ring-2 focus:ring-cyan-300">
                <option value="">Todos os planos</option>
                <option v-for="plano in opcoesPlano" :key="plano.value" :value="plano.value">{{ plano.label }}</option>
              </select>
            </label>
          </div>
        </div>

        <div class="relative mt-5 grid grid-cols-2 lg:grid-cols-4 gap-3">
          <div class="rounded-xl bg-white px-4 py-3 text-gray-800">
            <p class="text-xs font-medium text-gray-600">Pacientes cadastrados</p>
            <div v-if="carregando" class="mt-2 h-7 w-16 rounded bg-gray-200 animate-pulse" />
            <p v-else class="mt-1 text-2xl font-extrabold text-[#0f7a85]">{{ totalPatients.toLocaleString('pt-BR') }}</p>
          </div>
          <div class="rounded-xl bg-white px-4 py-3 text-gray-800">
            <p class="text-xs font-medium text-gray-600">Novos em {{ nomeMesAtual }}</p>
            <div v-if="carregando" class="mt-2 h-7 w-16 rounded bg-gray-200 animate-pulse" />
            <p v-else class="mt-1 text-2xl font-extrabold text-red-600">+{{ newThisMonth.toLocaleString('pt-BR') }}</p>
          </div>
          <div class="rounded-xl bg-white px-4 py-3 text-gray-800">
            <p class="text-xs font-medium text-gray-600">Páginas de parceiros ativas</p>
            <div v-if="carregando" class="mt-2 h-7 w-16 rounded bg-gray-200 animate-pulse" />
            <p v-else class="mt-1 text-2xl font-extrabold">{{ activeTenants }}<span class="ml-1 text-base font-semibold text-gray-400">/ {{ totalTenants }}</span></p>
          </div>
          <div class="rounded-xl bg-white px-4 py-3 text-gray-800">
            <p class="text-xs font-medium text-gray-600">Páginas sem cadastros no ano</p>
            <div v-if="carregando" class="mt-2 h-7 w-16 rounded bg-gray-200 animate-pulse" />
            <p v-else class="mt-1 text-2xl font-extrabold">{{ paginasSemCadastro.length }}</p>
          </div>
        </div>
      </section>

      <!-- Esqueleto enquanto os dados do período carregam -->
      <div v-if="carregando" class="space-y-5" aria-busy="true" aria-label="Carregando dados do dashboard">
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3">
          <div v-for="n in 5" :key="'sk-ind-' + n" class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm animate-pulse">
            <div class="flex items-center gap-2.5">
              <div class="h-8 w-8 rounded-lg bg-gray-200" />
              <div class="h-3 w-24 rounded bg-gray-200" />
            </div>
            <div class="mt-4 h-7 w-20 rounded bg-gray-200" />
            <div class="mt-2 h-3 w-32 rounded bg-gray-100" />
          </div>
        </div>

        <div v-for="linha in 2" :key="'sk-graf-' + linha" class="grid grid-cols-1 xl:grid-cols-3 gap-5">
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
            <div class="mt-2 h-3 w-32 rounded bg-gray-100" />
            <div v-if="linha === 1" class="mx-auto my-6 h-36 w-36 rounded-full border-[14px] border-gray-100" />
            <div class="space-y-3" :class="{ 'mt-5': linha === 2 }">
              <div v-for="n in (linha === 1 ? 3 : 4)" :key="n" class="h-10 rounded-xl bg-gray-100" />
            </div>
          </div>
        </div>

        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm animate-pulse">
          <div class="h-4 w-52 rounded bg-gray-200" />
          <div class="mt-2 h-3 w-64 rounded bg-gray-100" />
          <div class="mt-5 space-y-4">
            <div v-for="n in 5" :key="'sk-row-' + n" class="flex items-center gap-4">
              <div class="h-6 w-6 rounded-full bg-gray-200" />
              <div class="h-4 w-32 rounded bg-gray-200" />
              <div class="h-2 flex-1 max-w-[12rem] rounded-full bg-gray-100" />
              <div class="ml-auto h-4 w-16 rounded bg-gray-200" />
              <div class="h-4 w-16 rounded bg-gray-200" />
            </div>
          </div>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm animate-pulse">
          <div class="h-4 w-44 rounded bg-gray-200" />
          <div class="mt-4 grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div v-for="n in 4" :key="'sk-ben-' + n" class="h-16 rounded-xl bg-gray-100" />
          </div>
        </div>
      </div>

      <template v-else>
      <!-- Indicadores financeiros -->
      <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3">
        <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
          <div class="flex items-center gap-2.5">
            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-[#22b8cf] text-white"><DollarSign class="h-4 w-4" /></span>
            <span class="text-sm font-medium text-gray-700">Receita recorrente (MRR)</span>
          </div>
          <p class="mt-3 text-2xl font-extrabold text-gray-900">{{ fmtReal(mrr) }}</p>
          <p class="mt-1 text-xs text-gray-500">Vidas em uso × valor do plano</p>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
          <div class="flex items-center gap-2.5">
            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-red-600 text-white"><Heart class="h-4 w-4" /></span>
            <span class="text-sm font-medium text-gray-700">Vidas ativas</span>
          </div>
          <p class="mt-3 text-2xl font-extrabold text-gray-900">{{ vidasAtivas.toLocaleString('pt-BR') }}</p>
          <p class="mt-1 text-xs text-gray-500">Vagas em uso nos planos</p>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
          <div class="flex items-center gap-2.5">
            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-slate-800 text-white"><TrendingUp class="h-4 w-4" /></span>
            <span class="text-sm font-medium text-gray-700">Margem por vida</span>
          </div>
          <p class="mt-3 text-2xl font-extrabold text-gray-900">—</p>
          <p class="mt-1 text-xs text-gray-500">Mensalidade − custo telemedicina</p>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
          <div class="flex items-center gap-2.5">
            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-cyan-50 text-[#22b8cf]"><LogOut class="h-4 w-4" /></span>
            <span class="text-sm font-medium text-gray-700">Churn mensal</span>
          </div>
          <p class="mt-3 text-2xl font-extrabold text-gray-900">—</p>
          <p class="mt-1 text-xs text-gray-500">Cancelamentos ÷ vidas no início</p>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
          <div class="flex items-center gap-2.5">
            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-red-50 text-red-600"><AlertCircle class="h-4 w-4" /></span>
            <span class="text-sm font-medium text-gray-700">Inadimplência</span>
          </div>
          <p class="mt-3 text-2xl font-extrabold text-gray-900">—</p>
          <p class="mt-1 text-xs text-gray-500">Valor em aberto e % de vidas</p>
        </div>
      </div>

      <!-- Evolução + Cadastros por parceiro -->
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <div class="xl:col-span-2 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
          <div class="flex items-start justify-between gap-3">
            <div>
              <h3 class="text-base font-bold text-gray-900">Evolução da base de pacientes</h3>
              <p class="text-xs text-gray-500">Total acumulado de cadastros · {{ monthLabels[0]?.toLowerCase() }} a {{ monthLabels[ultimoMes - 1]?.toLowerCase() }} {{ currentYear }}</p>
            </div>
            <span v-if="crescimentoMes !== null" class="shrink-0 rounded-full bg-red-600 px-3 py-1 text-xs font-bold text-white">
              {{ crescimentoMes > 0 ? '+' : '' }}{{ crescimentoMes }}% em {{ monthNames[ultimoMes - 1].toLowerCase() }}
            </span>
          </div>
          <GraficoLinhaAcumulada class="mt-4" :valores="acumulado" :labels="monthLabels" />
        </div>

        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
          <h3 class="text-base font-bold text-gray-900">Cadastros por parceiro</h3>
          <p class="text-xs text-gray-500">Participação no total de {{ currentYear }}</p>
          <GraficoRosca :itens="fatias" rotulo="cadastros" vazio="Nenhum cadastro no ano." />
        </div>
      </div>

      <!-- Novos cadastros por mês + Pendências -->
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <div class="xl:col-span-2 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
          <div class="flex items-start justify-between gap-3">
            <div>
              <h3 class="text-base font-bold text-gray-900">Novos cadastros por mês</h3>
              <p class="text-xs text-gray-500">Total no ano: {{ totalAno }}</p>
            </div>
            <div class="flex shrink-0 items-center gap-3 text-xs text-gray-600">
              <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-[#22b8cf]" /> Meses anteriores</span>
              <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-red-600" /> Mês atual</span>
            </div>
          </div>
          <GraficoBarrasMes class="mt-4" :valores="mensal" :labels="monthLabels" :destaque="destaqueMes" />
        </div>

        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
          <h3 class="text-base font-bold text-gray-900">Pendências</h3>
          <div class="mt-4 space-y-2.5">
            <div class="flex items-center justify-between gap-3 rounded-xl bg-red-50 px-3 py-2.5">
              <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-900">Cadastros sem plano</p>
                <p class="text-xs text-gray-500">Contato ativo para assinatura</p>
              </div>
              <span class="text-lg font-extrabold text-gray-900">{{ totalPatients - patientsWithPlan }}</span>
            </div>
            <div class="flex items-center justify-between gap-3 rounded-xl bg-amber-50 px-3 py-2.5">
              <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-900">Páginas sem cadastros</p>
                <p class="truncate text-xs text-gray-500" :title="paginasSemCadastro.map(p => p.name).join(', ')">
                  {{ paginasSemCadastro.map(p => p.name).join(', ') || 'Nenhuma' }}
                </p>
              </div>
              <span class="text-lg font-extrabold text-gray-900">{{ paginasSemCadastro.length }}</span>
            </div>
            <div class="flex items-center justify-between gap-3 rounded-xl bg-cyan-50 px-3 py-2.5">
              <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-900">Falhas de envio de SMS</p>
                <p class="text-xs text-gray-500">Dos Logs de SMS</p>
              </div>
              <span class="text-lg font-extrabold text-gray-900">{{ smsFailed ?? '—' }}</span>
            </div>
            <div class="flex items-center justify-between gap-3 rounded-xl bg-gray-100 px-3 py-2.5">
              <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-900">Comissões a pagar</p>
                <p class="text-xs text-gray-500">Soma por parceiro no mês</p>
              </div>
              <span class="text-lg font-extrabold text-gray-900">—</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Desempenho por parceiro -->
      <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-2">
          <div>
            <h3 class="text-base font-bold text-gray-900">Desempenho por parceiro</h3>
            <p class="text-xs text-gray-500">Ordenado por receita; empate desfeito por cadastros</p>
          </div>
          <Link :href="route('pagina.index')" class="text-sm font-semibold text-[#0f7a85] underline underline-offset-2 hover:text-cyan-700">
            Ver todas as {{ pages.length }} páginas
          </Link>
        </div>
        <div class="mt-3 overflow-x-auto">
          <table class="w-full min-w-[640px] text-sm">
            <thead>
              <tr class="border-b text-xs uppercase tracking-wide text-gray-500">
                <th class="py-2.5 pl-2 text-left font-semibold">Parceiro</th>
                <th class="py-2.5 text-left font-semibold">Cadastros</th>
                <th class="py-2.5 text-right font-semibold">Com plano</th>
                <th class="py-2.5 text-right font-semibold">Receita/mês</th>
                <th class="py-2.5 pr-2 text-right font-semibold">Comissão</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(p, i) in desempenho" :key="p.id" class="border-b border-gray-100">
                <td class="py-3 pl-2">
                  <div class="flex items-center gap-3">
                    <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full text-xs font-bold"
                      :class="p.cor || (p.patients > 0 && i < 3) ? 'text-white' : 'bg-gray-100 text-gray-500'"
                      :style="p.cor || (p.patients > 0 && i < 3) ? { background: corDaPagina(p, i) } : {}">{{ i + 1 }}</span>
                    <span class="max-w-[10rem] truncate font-medium text-gray-800" :title="p.name">{{ p.name }}</span>
                    <span v-if="p.patients === 0" class="shrink-0 rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-semibold text-red-600">sem cadastros</span>
                  </div>
                </td>
                <td class="py-3 pr-6">
                  <div class="flex items-center gap-4">
                    <div class="h-2 w-full max-w-[12rem] rounded-full bg-gray-100">
                      <div class="h-2 rounded-full" :style="{ width: (p.patients / desempenhoMax * 100) + '%', background: corDaPagina(p, i) }" />
                    </div>
                    <span class="w-6 text-right font-bold text-gray-900">{{ p.patients }}</span>
                  </div>
                </td>
                <td class="py-3 text-right text-gray-700">{{ p.vidas }}</td>
                <td class="py-3 text-right text-gray-700">{{ fmtReal(p.receita) }}</td>
                <td class="py-3 pr-2 text-right text-gray-400">—</td>
              </tr>
              <tr v-if="!desempenho.length">
                <td colspan="5" class="py-10 text-center text-sm text-gray-400">Nenhuma página cadastrada.</td>
              </tr>
            </tbody>
            <tfoot v-if="desempenho.length">
              <tr class="font-bold text-gray-900">
                <td class="py-3 pl-2">Total ({{ desempenho.length }} de {{ pages.length }} páginas)</td>
                <td class="py-3 pr-6"><div class="flex items-center gap-4"><div class="w-full max-w-[12rem]" /><span class="w-6 text-right">{{ desempenhoTotal }}</span></div></td>
                <td class="py-3 text-right">{{ desempenhoVidas }}</td>
                <td class="py-3 text-right">{{ fmtReal(desempenhoReceita) }}</td>
                <td class="py-3 pr-2 text-right text-gray-400">—</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Plano de Benefícios -->
      <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
        <div class="flex items-start gap-3">
          <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-[#0f7a85] text-white"><Gift class="h-4 w-4" /></span>
          <div>
            <h3 class="text-base font-bold text-gray-900">{{ planoBeneficios.nome }}</h3>
            <p class="text-xs text-gray-500">Plano próprio do sistema · {{ beneficios.parceiros.length }} parceiro(s) com o plano habilitado</p>
          </div>
        </div>

        <p v-if="!beneficios.parceiros.length" class="mt-6 rounded-xl border border-dashed border-gray-200 py-8 text-center text-sm text-gray-400">
          Nenhum parceiro com o {{ planoBeneficios.nome }} habilitado{{ selectedPage ? ' neste filtro' : '' }}.
          Habilite na aba Planos da Página de Parceiros.
        </p>

        <template v-else>
          <div class="mt-4 grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="rounded-xl bg-cyan-50 px-4 py-3">
              <p class="text-xs font-medium text-gray-600">Beneficiários</p>
              <p class="mt-1 text-2xl font-extrabold text-[#0f7a85]">{{ beneficios.beneficiarios.toLocaleString('pt-BR') }}</p>
            </div>
            <div class="rounded-xl bg-red-50 px-4 py-3">
              <p class="text-xs font-medium text-gray-600">Novos em {{ nomeMesAtual }}</p>
              <p class="mt-1 text-2xl font-extrabold text-red-600">+{{ beneficios.novosMes }}</p>
            </div>
            <div class="rounded-xl bg-gray-100 px-4 py-3">
              <p class="text-xs font-medium text-gray-600">Vagas disponíveis</p>
              <p class="mt-1 text-2xl font-extrabold text-gray-900">
                {{ beneficios.disponiveis }}<span class="ml-1 text-base font-semibold text-gray-400">/ {{ beneficios.contratadas }}</span>
              </p>
            </div>
            <div class="rounded-xl bg-amber-50 px-4 py-3">
              <p class="text-xs font-medium text-gray-600">Receita mensal</p>
              <p class="mt-1 text-2xl font-extrabold text-gray-900">{{ fmtReal(beneficios.receita) }}</p>
            </div>
          </div>

          <div class="mt-5 grid grid-cols-1 xl:grid-cols-3 gap-5">
            <div class="xl:col-span-2 overflow-x-auto">
              <table class="w-full min-w-[560px] text-sm">
                <thead>
                  <tr class="border-b text-xs uppercase tracking-wide text-gray-500">
                    <th class="py-2.5 pl-2 text-left font-semibold">Parceiro</th>
                    <th class="py-2.5 text-left font-semibold">Ocupação</th>
                    <th class="py-2.5 text-right font-semibold">Valor</th>
                    <th class="py-2.5 pr-2 text-right font-semibold">Receita/mês</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="p in beneficios.parceiros" :key="p.tenant_id" class="border-b border-gray-100">
                    <td class="py-3 pl-2">
                      <span class="flex items-center gap-2">
                        <span v-if="p.cor" class="h-2.5 w-2.5 shrink-0 rounded-sm" :style="{ background: p.cor }" />
                        <span class="block max-w-[12rem] truncate font-medium text-gray-800" :title="p.nome">{{ p.nome }}</span>
                      </span>
                    </td>
                    <td class="py-3 pr-6">
                      <div class="flex items-center gap-3">
                        <div class="h-2 w-full max-w-[10rem] rounded-full bg-gray-100">
                          <div class="h-2 rounded-full" :class="!p.cor && (ocupacao(p) >= 100 ? 'bg-red-600' : 'bg-[#22b8cf]')"
                            :style="{ width: ocupacao(p) + '%', background: p.cor || undefined }" />
                        </div>
                        <span class="whitespace-nowrap text-gray-700"><strong class="text-gray-900">{{ p.beneficiarios }}</strong> / {{ p.quantidade }}</span>
                      </div>
                    </td>
                    <td class="py-3 text-right text-gray-700">{{ p.valor !== null ? fmtReal(p.valor) : '—' }}</td>
                    <td class="py-3 pr-2 text-right font-semibold text-gray-900">{{ fmtReal(p.receita) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div>
              <p class="text-sm font-semibold text-gray-900">Novos beneficiários por mês</p>
              <p class="text-xs text-gray-500">Total em {{ currentYear }}: {{ beneficios.novosAno }}</p>
              <div class="mt-3 flex h-28 items-end gap-1.5 border-b border-gray-200">
                <div v-for="(v, i) in beneficios.porMes" :key="i" class="flex h-full flex-1 flex-col items-center justify-end" :title="`${monthLabels[i]}: ${v}`">
                  <span v-if="v > 0" class="mb-0.5 text-[10px] font-bold text-gray-900">{{ v }}</span>
                  <div class="w-full rounded-t"
                    :class="i + 1 === currentMonth && currentYear === anoAtual ? 'bg-red-600' : 'bg-[#22b8cf]'"
                    :style="{ height: v > 0 ? (v / beneficiosMaxMes * 80) + '%' : '2px', opacity: v > 0 ? 1 : 0.25 }" />
                </div>
              </div>
              <div class="mt-1 flex gap-1.5">
                <span v-for="l in monthLabels" :key="l" class="flex-1 text-center text-[9px] text-gray-500">{{ l[0] }}</span>
              </div>
            </div>
          </div>
        </template>
      </div>
      </template>

      <!-- Telemedicina -->
      <section>
        <div class="mb-3 flex items-center gap-3">
          <h3 class="text-base font-bold text-gray-900">Uso e custo da telemedicina</h3>
          <span class="rounded-full bg-cyan-50 px-2.5 py-0.5 text-xs font-semibold text-[#0f7a85]">requer integração com o fornecedor</span>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3">
          <div v-for="item in [
              { icon: Video, cor: 'bg-[#22b8cf] text-white', titulo: 'Consultas no mês', desc: 'Total realizado' },
              { icon: Activity, cor: 'bg-red-600 text-white', titulo: 'Taxa de utilização', desc: 'Consultas ÷ vidas ativas' },
              { icon: PieChart, cor: 'bg-slate-800 text-white', titulo: 'Sinistralidade', desc: 'Custo consultas ÷ receita' },
              { icon: Clock, cor: 'bg-cyan-50 text-[#22b8cf]', titulo: 'Espera média', desc: 'Até início do atendimento' },
              { icon: Star, cor: 'bg-red-50 text-red-600', titulo: 'NPS', desc: 'Avaliação pós-consulta' },
            ]" :key="item.titulo"
            class="rounded-2xl border border-dashed border-gray-300 bg-white p-4">
            <span class="grid h-8 w-8 place-items-center rounded-lg" :class="item.cor"><component :is="item.icon" class="h-4 w-4" /></span>
            <p class="mt-3 text-sm font-medium text-gray-700">{{ item.titulo }}</p>
            <p class="mt-2 text-xl font-extrabold text-gray-300">—</p>
            <p class="mt-2 text-xs text-gray-500">{{ item.desc }}</p>
          </div>
        </div>
      </section>

      <!-- Vidas por plano -->
      <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
        <h3 class="text-base font-bold text-gray-900">Vidas por plano</h3>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
          <div v-for="(g, i) in vidasPorPlano" :key="g.cod_plano"
            class="rounded-xl border p-4" :class="CORES_PLANO[i % CORES_PLANO.length]">
            <p class="truncate text-sm font-bold text-gray-900" :title="g.plano">{{ g.plano }}</p>
            <p class="text-xs text-gray-500">Mensalidade: {{ mensalidade(g) }}</p>
            <div class="mt-2 flex items-end justify-between">
              <p class="text-2xl font-extrabold text-gray-900">{{ g.vidas }} <span class="text-xs font-normal text-gray-500">vidas</span></p>
              <p class="text-sm font-semibold text-gray-700">{{ fmtReal(g.receita) }}/mês</p>
            </div>
          </div>
          <p v-if="!vidasPorPlano.length" class="md:col-span-3 py-6 text-center text-sm text-gray-400">
            Nenhum plano configurado nas Páginas de Parceiros.
          </p>
        </div>
        <p class="mt-3 text-xs text-gray-500">
          Ticket médio: {{ ticketMedio !== null ? fmtReal(ticketMedio) : '— (sem vidas ativas)' }}
        </p>
      </div>

    </div>
  </CentralAdminLayout>
</template>
