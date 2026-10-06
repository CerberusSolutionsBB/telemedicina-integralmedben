<script setup>
import { Head, Link } from "@inertiajs/vue3";
import CentralAdminLayout from "@/Layouts/CentralAdminLayout.vue";
import {
  DollarSign, Heart, TrendingUp, LogOut, AlertCircle,
  Video, Activity, PieChart, Clock, Star,
} from "lucide-vue-next";
import { ref, computed } from "vue";

const props = defineProps({
  pages:             { type: Array,  default: () => [] },
  planosParceiros:   { type: Array,  default: () => [] },
  updatedAt:         { type: String, default: '' },
});

const PALETA = ['#22b8cf', '#e11d2e', '#1e293b', '#f59e0b', '#94a3b8'];

// Cor indicativa definida na Página de Parceiros; sem ela, cor padrão pela posição.
const corDaPagina = (pagina, i) => pagina?.cor || PALETA[i % PALETA.length];

const selectedPage = ref(null);
const selectedPlano = ref('');

const fmtReal = (v) => Number(v || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

const opcoesPlano = computed(() => {
  const mapa = new Map(props.planosParceiros.map(p => [p.cod_plano, p.plano]));
  return [...mapa].map(([value, label]) => ({ value, label }));
});

const planosDoPlano = computed(() =>
  props.planosParceiros.filter(p => !selectedPlano.value || p.cod_plano === selectedPlano.value));

const planosFiltrados = computed(() =>
  planosDoPlano.value.filter(p => !selectedPage.value || p.tenant_id === selectedPage.value));

const vidasContratadas = computed(() => planosFiltrados.value.reduce((a, p) => a + (p.quantidade || 0), 0));
const mrr = computed(() => planosFiltrados.value.reduce((a, p) => a + p.em_uso * (p.valor || 0), 0));

const paginasFiltradas = computed(() => props.pages.filter(p => !selectedPage.value || p.id === selectedPage.value));
const beneficiariosAtivos = computed(() => paginasFiltradas.value.reduce((a, p) => a + (p.ativos || 0), 0));
const beneficiariosInativos = computed(() => paginasFiltradas.value.reduce((a, p) => a + (p.inativos || 0), 0));

const receitaDaPagina = (id) => planosDoPlano.value.filter(p => p.tenant_id === id).reduce((a, p) => a + p.em_uso * (p.valor || 0), 0);
const vidasDaPagina = (id) => planosDoPlano.value.filter(p => p.tenant_id === id).reduce((a, p) => a + p.em_uso, 0);

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
  <Head title="Financeiro" />

  <CentralAdminLayout>
    <div class="py-6 space-y-5">

      <!-- Cabeçalho -->
      <section class="relative overflow-hidden rounded-2xl bg-[#23BACF] px-5 sm:px-6 pt-5 pb-5 text-white">
        <DollarSign class="pointer-events-none absolute -right-6 -top-6 h-36 w-36 text-white/10" />
        <div class="relative flex flex-col lg:flex-row lg:items-start justify-between gap-4">
          <div>
            <h2 class="text-2xl font-extrabold uppercase tracking-wide">Financeiro</h2>
            <p class="mt-1 text-sm text-white/90">Receita recorrente, custos e desempenho por parceiro · atualizado em {{ updatedAt }}</p>
          </div>
          <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-2">
            <label class="flex flex-col gap-1 text-xs text-white/90">
              Parceiro
              <select v-model="selectedPage"
                class="rounded-lg border-0 bg-white px-3 py-2 pr-8 text-sm font-medium text-gray-800 focus:ring-2 focus:ring-cyan-300">
                <option :value="null">Todos os parceiros</option>
                <option v-for="page in pages" :key="page.id" :value="page.id">{{ page.name }}</option>
              </select>
            </label>
            <label class="flex flex-col gap-1 text-xs text-white/90">
              Plano
              <select v-model="selectedPlano"
                class="rounded-lg border-0 bg-white px-3 py-2 pr-8 text-sm font-medium text-gray-800 focus:ring-2 focus:ring-cyan-300">
                <option value="">Todos os planos</option>
                <option v-for="plano in opcoesPlano" :key="plano.value" :value="plano.value">{{ plano.label }}</option>
              </select>
            </label>
          </div>
        </div>
      </section>

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
          <p class="mt-3 text-2xl font-extrabold text-gray-900">{{ beneficiariosAtivos.toLocaleString('pt-BR') }}<span class="ml-1 text-base font-semibold text-gray-500">/ {{ vidasContratadas.toLocaleString('pt-BR') }}</span></p>
          <p class="mt-1 text-xs text-gray-500">Beneficiários ativos do total contratado</p>
          <p class="mt-1.5 text-xs font-semibold text-red-600">{{ beneficiariosInativos.toLocaleString('pt-BR') }} inativos</p>
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

      <!-- Desempenho por parceiro -->
      <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-2">
          <div>
            <h3 class="text-base font-bold text-gray-900">Desempenho por parceiro</h3>
            <p class="text-xs text-gray-500">Ordenado por receita; empate desfeito por cadastros</p>
          </div>
          <Link :href="route('pagina.index')" class="text-sm font-semibold text-[#23BACF] underline underline-offset-2 hover:text-cyan-700">
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
                    <span v-if="p.patients === 0" class="shrink-0 rounded-full bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-600">sem cadastros</span>
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
                <td class="py-3 pr-2 text-right text-gray-500">—</td>
              </tr>
              <tr v-if="!desempenho.length">
                <td colspan="5" class="py-10 text-center text-sm text-gray-500">Nenhuma página cadastrada.</td>
              </tr>
            </tbody>
            <tfoot v-if="desempenho.length">
              <tr class="font-bold text-gray-900">
                <td class="py-3 pl-2">Total ({{ desempenho.length }} de {{ pages.length }} páginas)</td>
                <td class="py-3 pr-6"><div class="flex items-center gap-4"><div class="w-full max-w-[12rem]" /><span class="w-6 text-right">{{ desempenhoTotal }}</span></div></td>
                <td class="py-3 text-right">{{ desempenhoVidas }}</td>
                <td class="py-3 text-right">{{ fmtReal(desempenhoReceita) }}</td>
                <td class="py-3 pr-2 text-right text-gray-500">—</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Comissões a pagar -->
      <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
        <h3 class="text-base font-bold text-gray-900">A pagar</h3>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
          <div class="flex items-center justify-between gap-3 rounded-xl bg-gray-100 px-4 py-3">
            <div class="min-w-0">
              <p class="text-sm font-semibold text-gray-900">Comissões a pagar</p>
              <p class="text-xs text-gray-500">Soma por parceiro no mês</p>
            </div>
            <span class="text-lg font-extrabold text-gray-900">—</span>
          </div>
        </div>
      </div>

      <!-- Telemedicina -->
      <section>
        <div class="mb-3 flex items-center gap-3">
          <h3 class="text-base font-bold text-gray-900">Uso e custo da telemedicina</h3>
          <span class="rounded-full bg-cyan-50 px-2.5 py-0.5 text-xs font-semibold text-[#23BACF]">requer integração com o fornecedor</span>
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
            <p class="mt-2 text-xl font-extrabold text-gray-500">—</p>
            <p class="mt-2 text-xs text-gray-500">{{ item.desc }}</p>
          </div>
        </div>
      </section>

    </div>
  </CentralAdminLayout>
</template>
