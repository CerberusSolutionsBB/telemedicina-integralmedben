<script setup>
import { Head, router } from "@inertiajs/vue3";
import CentralAdminLayout from "@/Layouts/CentralAdminLayout.vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import { Building2, Users, UserPlus, ExternalLink, Search, X, TrendingUp } from "lucide-vue-next";
import { ref, computed } from "vue";

const props = defineProps({
  totalTenants:            { type: Number, default: 0 },
  activeTenants:           { type: Number, default: 0 },
  totalPatients:           { type: Number, default: 0 },
  patientsWithPlan:        { type: Number, default: 0 },
  newThisMonth:            { type: Number, default: 0 },
  monthlyGrowth:           { type: Array,  default: () => [] },
  currentYear:             { type: Number, default: new Date().getFullYear() },
  pages:                   { type: Array,  default: () => [] },
  tenantMonthlyGrowth:     { type: Object, default: () => ({}) },
  monthLabels:             { type: Array,  default: () => [] },
});

const selectedPage = ref(null);
const pageSearch = ref('');

const filteredPages = computed(() => {
  const q = pageSearch.value.toLowerCase().trim();
  if (!q) return props.pages;
  return props.pages.filter(p => p.name.toLowerCase().includes(q));
});

const filteredMonthlyGrowth = computed(() => {
  if (!selectedPage.value) return props.monthlyGrowth;
  const tenantData = props.tenantMonthlyGrowth[selectedPage.value] || [];
  return props.monthLabels.map((label, i) => ({ label, value: tenantData[i] || 0 }));
});

const availableYears = Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - i);

const goToYear = (year) => {
  router.visit(route('dashboard', { year }), { preserveState: true, preserveScroll: true, replace: true });
};

const monthName = new Date().toLocaleString('pt-BR', { month: 'long' });

const maxValue = computed(() => Math.max(...filteredMonthlyGrowth.value.map(d => d.value), 1));

const yTicks = computed(() => {
  const max = maxValue.value;
  const step = Math.ceil(max / 4);
  return [step * 4, step * 3, step * 2, step, 0];
});
</script>

<template>
  <Head title="Dashboard" />

  <CentralAdminLayout>
    <div class="space-y-6 sm:space-y-8 max-w-7xl mx-auto">

      <!-- Cabeçalho -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h1 class="text-xl sm:text-2xl font-extrabold text-gray-900 tracking-tight">Dashboard</h1>
          <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-2 text-sm text-gray-500">
            <span class="inline-flex items-center gap-1.5 font-semibold text-gray-800">
              <span class="w-2 h-2 rounded-full bg-cyan-500" /> {{ activeTenants }} páginas ativas
            </span>
            <span class="hidden sm:inline text-gray-300">·</span>
            <span class="inline-flex items-center gap-1.5 font-semibold text-gray-800">
              <span class="w-2 h-2 rounded-full bg-indigo-500" /> {{ totalPatients.toLocaleString('pt-BR') }} pacientes
            </span>
            <span class="hidden sm:inline text-gray-300">·</span>
            <span class="inline-flex items-center gap-1.5 font-semibold text-emerald-600">
              <span class="w-2 h-2 rounded-full bg-emerald-500" /> {{ newThisMonth }} novos
            </span>
          </div>
        </div>
        <select
          :value="currentYear"
          @change="goToYear($event.target.value)"
          class="self-start sm:self-auto text-sm border border-gray-200 rounded-xl px-4 py-2.5 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-cyan-500 shadow-sm"
        >
          <option v-for="y in availableYears" :key="y" :value="y">{{ y }}</option>
        </select>
      </div>

      <!-- KPIs -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
        <Card class="border-0 bg-gradient-to-br from-cyan-50 to-white shadow-sm ring-1 ring-cyan-100">
          <CardContent class="flex items-center gap-4 p-4 sm:p-5">
            <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl bg-cyan-100 flex items-center justify-center shrink-0">
              <Building2 class="w-5 h-5 sm:w-6 sm:h-6 text-cyan-600" />
            </div>
            <div>
              <p class="text-[11px] sm:text-xs font-semibold text-gray-500 uppercase tracking-wider">Páginas ativas</p>
              <p class="text-xl sm:text-2xl font-extrabold text-cyan-700">{{ activeTenants }}<span class="text-sm font-normal text-gray-400 ml-1">/{{ totalTenants }}</span></p>
            </div>
          </CardContent>
        </Card>

        <Card class="border-0 bg-gradient-to-br from-indigo-50 to-white shadow-sm ring-1 ring-indigo-100">
          <CardContent class="flex items-center gap-4 p-4 sm:p-5">
            <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl bg-indigo-100 flex items-center justify-center shrink-0">
              <Users class="w-5 h-5 sm:w-6 sm:h-6 text-indigo-600" />
            </div>
            <div>
              <p class="text-[11px] sm:text-xs font-semibold text-gray-500 uppercase tracking-wider">Pacientes</p>
              <p class="text-xl sm:text-2xl font-extrabold text-indigo-700">{{ totalPatients.toLocaleString('pt-BR') }}</p>
              <p class="text-[10px] sm:text-xs text-gray-500 mt-0.5">{{ patientsWithPlan }} c/ plano · {{ totalPatients - patientsWithPlan }} s/</p>
            </div>
          </CardContent>
        </Card>

        <Card class="border-0 bg-gradient-to-br from-emerald-50 to-white shadow-sm ring-1 ring-emerald-100">
          <CardContent class="flex items-center gap-4 p-4 sm:p-5">
            <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl bg-emerald-100 flex items-center justify-center shrink-0">
              <TrendingUp class="w-5 h-5 sm:w-6 sm:h-6 text-emerald-600" />
            </div>
            <div>
              <p class="text-[11px] sm:text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ monthName }}</p>
              <p class="text-xl sm:text-2xl font-extrabold text-emerald-700">{{ newThisMonth.toLocaleString('pt-BR') }}</p>
              <p class="text-[10px] sm:text-xs text-gray-500 mt-0.5">novos cadastros</p>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Gráfico -->
      <Card class="shadow-sm">
        <CardHeader class="pb-1 pt-4 sm:pt-5 px-4 sm:px-5 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
          <div>
            <CardTitle class="text-sm sm:text-base font-bold text-gray-800">Cadastros por mês — {{ currentYear }}</CardTitle>
            <p class="text-[11px] sm:text-xs text-gray-500 mt-0.5">
              {{ selectedPage ? (pages.find(p => p.id === selectedPage)?.name || 'Filtrado') : 'Todos os parceiros' }}
            </p>
          </div>
          <select
            v-model="selectedPage"
            class="self-start sm:self-auto text-[11px] sm:text-xs border border-gray-200 rounded-lg px-3 py-2 bg-white text-gray-600 focus:outline-none focus:ring-2 focus:ring-cyan-500"
          >
            <option :value="null">Todos os parceiros</option>
            <option v-for="page in pages" :key="page.id" :value="page.id">{{ page.name }}</option>
          </select>
        </CardHeader>
        <CardContent class="pt-0 pb-3 sm:pb-4 px-2 sm:px-5">
          <div class="relative flex h-36 sm:h-48">
            <div class="flex flex-col justify-between pr-1.5 sm:pr-2 text-[9px] sm:text-[10px] text-gray-400 w-7 sm:w-8 text-right shrink-0">
              <span v-for="tick in yTicks" :key="tick">{{ tick }}</span>
            </div>
            <div class="flex-1 flex items-end gap-0.5 sm:gap-1 border-l border-b border-gray-200 pl-1.5 sm:pl-2 pb-4 sm:pb-5 relative min-w-0">
              <div
                v-for="(item, i) in filteredMonthlyGrowth"
                :key="i"
                class="flex-1 flex flex-col items-center justify-end h-full group relative min-w-0"
              >
                <div class="absolute -top-7 sm:-top-8 opacity-0 group-hover:opacity-100 transition-opacity text-[9px] sm:text-[10px] font-bold bg-gray-800 text-white rounded px-1.5 py-0.5 shadow whitespace-nowrap z-10">
                  {{ item.value }}
                </div>
                <div
                  class="w-full rounded-t transition-all duration-300 hover:opacity-80"
                  :class="selectedPage ? 'bg-indigo-500' : 'bg-cyan-500'"
                  :style="{ height: (item.value / maxValue) * 100 + '%', maxWidth: '2rem' }"
                />
                <span class="absolute -bottom-3 sm:-bottom-4 text-[9px] sm:text-[10px] font-medium text-gray-400 truncate w-full text-center">
                  {{ item.label }}
                </span>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Páginas -->
      <Card class="shadow-sm">
        <CardHeader class="pb-2 pt-4 sm:pt-5 px-4 sm:px-5">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <CardTitle class="text-sm sm:text-base font-bold text-gray-800 flex items-center gap-2 shrink-0">
              <Building2 class="w-4 h-4 text-gray-400" />
              Páginas
              <span class="text-xs font-normal text-gray-500 bg-gray-100 rounded-full px-2 py-0.5">{{ filteredPages.length }}/{{ pages.length }}</span>
            </CardTitle>
            <div class="relative w-full sm:max-w-xs">
              <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
              <input
                v-model="pageSearch"
                type="text"
                placeholder="Buscar página..."
                class="w-full pl-9 pr-8 py-2.5 text-sm border border-gray-200 rounded-xl bg-white text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-cyan-500"
              />
              <button v-if="pageSearch" @click="pageSearch = ''"
                class="absolute inset-y-0 right-2 flex items-center text-gray-400 hover:text-gray-600">
                <X class="w-4 h-4" />
              </button>
            </div>
          </div>
        </CardHeader>
        <CardContent class="p-0">
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b bg-gray-50/80">
                  <th class="text-left px-4 sm:px-5 py-2.5 sm:py-3 font-semibold text-gray-500 text-xs sm:text-sm">Página</th>
                  <th class="text-right px-4 sm:px-5 py-2.5 sm:py-3 font-semibold text-gray-500 text-xs sm:text-sm">Pacientes</th>
                  <th class="text-center px-4 sm:px-5 py-2.5 sm:py-3 font-semibold text-gray-500 text-xs sm:text-sm">Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="page in filteredPages" :key="page.id" class="border-b hover:bg-gray-50/50 transition-colors">
                  <td class="px-4 sm:px-5 py-3 sm:py-3.5">
                    <div class="flex items-center gap-2">
                      <span class="font-semibold text-gray-800 truncate">{{ page.name }}</span>
                      <a v-if="page.url" :href="page.url" target="_blank"
                        class="shrink-0 p-1.5 sm:p-1 text-cyan-500 hover:text-cyan-700 hover:bg-cyan-50 rounded-lg transition-colors"
                        :title="'Abrir ' + page.subdomain">
                        <ExternalLink class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                      </a>
                    </div>
                  </td>
                  <td class="text-right px-4 sm:px-5 py-3 sm:py-3.5">
                    <span class="font-bold text-gray-800">{{ page.patients }}</span>
                  </td>
                  <td class="text-center px-4 sm:px-5 py-3 sm:py-3.5">
                    <span v-if="page.status" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] sm:text-xs font-bold bg-emerald-100 text-emerald-700">
                      <span class="w-2 h-2 rounded-full bg-emerald-500" /> Ativo
                    </span>
                    <span v-else class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] sm:text-xs font-bold bg-red-100 text-red-700">
                      <span class="w-2 h-2 rounded-full bg-red-500" /> Inativo
                    </span>
                  </td>
                </tr>
                <tr v-if="!filteredPages.length">
                  <td colspan="3" class="text-center py-12 text-gray-400 text-sm">
                    {{ pageSearch ? 'Nenhuma página encontrada para "' + pageSearch + '"' : 'Nenhuma página cadastrada.' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

    </div>
  </CentralAdminLayout>
</template>
