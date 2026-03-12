<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import CentralAdminLayout from "@/Layouts/CentralAdminLayout.vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from "@/Components/ui/table";
import { Users, MapPin, BarChart3, Building2 } from "lucide-vue-next";
import { ref, watch } from "vue";

const props = defineProps({
  patients:       { type: Object, required: true },
  byMonth:        { type: Array,  default: () => [] },
  byPlan:         { type: Array,  default: () => [] },
  byCity:         { type: Array,  default: () => [] },
  byTenant:       { type: Array,  default: () => [] },
  planOptions:    { type: Array,  default: () => [] },
  cityOptions:    { type: Array,  default: () => [] },
  tenants:        { type: Array,  default: () => [] },
  filters:        { type: Object, default: () => ({}) },
  years:          { type: Array,  default: () => [] },
  planQuestionId: { type: Number, default: null },
  cityQuestionId: { type: Number, default: null },
});

const year     = ref(props.filters.year      ?? new Date().getFullYear());
const month    = ref(props.filters.month     ?? "");
const plan     = ref(props.filters.plan      ?? "");
const city     = ref(props.filters.city      ?? "");
const tenantId = ref(props.filters.tenant_id ?? "");

watch([year, month, plan, city, tenantId], () => {
  router.get(
    route("admin.reports.index"),
    {
      year:      year.value,
      month:     month.value     || undefined,
      plan:      plan.value      || undefined,
      city:      city.value      || undefined,
      tenant_id: tenantId.value  || undefined,
    },
    { preserveScroll: true, replace: true }
  );
});

const monthNames = [
  "", "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho",
  "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro",
];

const totalPatients = props.byMonth.reduce((sum, m) => sum + m.total, 0);

const getPlanLabel = (value) =>
  props.planOptions.find((p) => p.value === value)?.label ?? value;

const getTenantName = (id) =>
  props.tenants.find((t) => t.id === id)?.name ?? id;

const getAnswer = (patient, questionId) =>
  patient.answers?.find((a) => a.question_id === questionId)?.answer ?? "-";
</script>

<template>
  <Head title="Relatório de Clientes" />

  <CentralAdminLayout>
    <div class="space-y-6">
      <h1 class="text-xl font-bold">Relatório de Clientes</h1>

      <!-- Filtros -->
      <div class="flex flex-wrap gap-3">
        <select v-model="year"
          class="border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500">
          <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
        </select>

        <select v-model="month"
          class="border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500">
          <option value="">Todos os meses</option>
          <option v-for="(name, idx) in monthNames.slice(1)" :key="idx+1" :value="idx+1">{{ name }}</option>
        </select>

        <select v-model="tenantId"
          class="border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500">
          <option value="">Todos os credenciados</option>
          <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.name }}</option>
        </select>

        <select v-if="planOptions.length" v-model="plan"
          class="border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500">
          <option value="">Todos os planos</option>
          <option v-for="p in planOptions" :key="p.value" :value="p.value">{{ p.label }}</option>
        </select>

        <!-- Cidade: dropdown quando há opções, input livre quando ainda sem dados -->
        <select v-if="cityOptions.length" v-model="city"
          class="border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500">
          <option value="">Todas as cidades</option>
          <option v-for="c in cityOptions" :key="c" :value="c">{{ c }}</option>
        </select>
        <input v-else-if="cityQuestionId" v-model="city" type="text" placeholder="Filtrar por cidade..."
          class="border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 w-44" />
      </div>

      <!-- Cards de totais -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground">Total no período</CardTitle>
            <Users class="w-4 h-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-3xl font-bold">{{ totalPatients }}</div>
            <p class="text-xs text-muted-foreground mt-1">clientes cadastrados</p>
          </CardContent>
        </Card>

        <Card v-if="byTenant.length && !tenantId">
          <CardHeader class="flex flex-row items-center justify-between pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground">Maior credenciado</CardTitle>
            <Building2 class="w-4 h-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-xl font-bold truncate">{{ getTenantName(byTenant[0]?.tenant_id) }}</div>
            <p class="text-xs text-muted-foreground mt-1">{{ byTenant[0]?.total }} clientes</p>
          </CardContent>
        </Card>

        <Card v-if="byPlan.length">
          <CardHeader class="flex flex-row items-center justify-between pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground">Plano mais comum</CardTitle>
            <BarChart3 class="w-4 h-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ getPlanLabel(byPlan[0]?.plan) }}</div>
            <p class="text-xs text-muted-foreground mt-1">{{ byPlan[0]?.total }} clientes</p>
          </CardContent>
        </Card>

        <Card v-if="byCity.length">
          <CardHeader class="flex flex-row items-center justify-between pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground">Cidade mais comum</CardTitle>
            <MapPin class="w-4 h-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ byCity[0]?.city }}</div>
            <p class="text-xs text-muted-foreground mt-1">{{ byCity[0]?.total }} clientes</p>
          </CardContent>
        </Card>
      </div>

      <!-- Tabelas de breakdown -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

        <!-- Por mês -->
        <div class="bg-white border rounded-xl border-gray-200 shadow-sm overflow-hidden">
          <div class="px-4 py-3 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700">Por Mês</h2>
          </div>
          <Table>
            <TableHeader><TableRow>
              <TableHead>Mês</TableHead>
              <TableHead class="text-right">Total</TableHead>
            </TableRow></TableHeader>
            <TableBody>
              <TableRow v-for="m in byMonth" :key="`${m.year}-${m.month}`">
                <TableCell class="text-sm">{{ monthNames[m.month] }}</TableCell>
                <TableCell class="text-right font-semibold text-sm">{{ m.total }}</TableCell>
              </TableRow>
              <TableRow v-if="byMonth.length === 0">
                <TableCell colspan="2" class="text-center text-sm text-muted-foreground py-4">Sem dados</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <!-- Por credenciado -->
        <div v-if="!tenantId" class="bg-white border rounded-xl border-gray-200 shadow-sm overflow-hidden">
          <div class="px-4 py-3 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700">Por Credenciado</h2>
          </div>
          <Table>
            <TableHeader><TableRow>
              <TableHead>Credenciado</TableHead>
              <TableHead class="text-right">Total</TableHead>
            </TableRow></TableHeader>
            <TableBody>
              <TableRow v-for="t in byTenant" :key="t.tenant_id">
                <TableCell class="text-sm truncate max-w-[120px]" :title="getTenantName(t.tenant_id)">
                  {{ getTenantName(t.tenant_id) }}
                </TableCell>
                <TableCell class="text-right font-semibold text-sm">{{ t.total }}</TableCell>
              </TableRow>
              <TableRow v-if="byTenant.length === 0">
                <TableCell colspan="2" class="text-center text-sm text-muted-foreground py-4">Sem dados</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <!-- Por plano -->
        <div v-if="byPlan.length" class="bg-white border rounded-xl border-gray-200 shadow-sm overflow-hidden">
          <div class="px-4 py-3 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700">Por Plano</h2>
          </div>
          <Table>
            <TableHeader><TableRow>
              <TableHead>Plano</TableHead>
              <TableHead class="text-right">Total</TableHead>
            </TableRow></TableHeader>
            <TableBody>
              <TableRow v-for="p in byPlan" :key="p.plan">
                <TableCell class="text-sm">{{ getPlanLabel(p.plan) }}</TableCell>
                <TableCell class="text-right font-semibold text-sm">{{ p.total }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <!-- Por cidade -->
        <div v-if="byCity.length" class="bg-white border rounded-xl border-gray-200 shadow-sm overflow-hidden">
          <div class="px-4 py-3 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700">Por Cidade</h2>
          </div>
          <Table>
            <TableHeader><TableRow>
              <TableHead>Cidade</TableHead>
              <TableHead class="text-right">Total</TableHead>
            </TableRow></TableHeader>
            <TableBody>
              <TableRow v-for="c in byCity" :key="c.city">
                <TableCell class="text-sm">{{ c.city }}</TableCell>
                <TableCell class="text-right font-semibold text-sm">{{ c.total }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </div>

      <!-- Lista de pacientes -->
      <div class="bg-white border rounded-xl border-gray-200 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100">
          <h2 class="text-sm font-semibold text-gray-700">Clientes ({{ patients.total }})</h2>
        </div>
        <div class="overflow-x-auto">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="text-center">#</TableHead>
                <TableHead class="text-center">Credenciado</TableHead>
                <TableHead v-if="planQuestionId" class="text-center">Plano</TableHead>
                <TableHead v-if="cityQuestionId" class="text-center">Cidade</TableHead>
                <TableHead class="text-center">Cadastrado em</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="p in patients.data" :key="p.id">
                <TableCell class="text-center text-sm text-muted-foreground">{{ p.id }}</TableCell>
                <TableCell class="text-center text-sm font-medium">{{ getTenantName(p.tenant_id) }}</TableCell>
                <TableCell v-if="planQuestionId" class="text-center text-sm">
                  {{ getPlanLabel(getAnswer(p, planQuestionId)) }}
                </TableCell>
                <TableCell v-if="cityQuestionId" class="text-center text-sm">
                  {{ getAnswer(p, cityQuestionId) }}
                </TableCell>
                <TableCell class="text-center text-sm">
                  {{ new Date(p.created_at).toLocaleDateString('pt-BR') }}
                </TableCell>
              </TableRow>
              <TableRow v-if="patients.data.length === 0">
                <TableCell colspan="5" class="text-center text-sm text-muted-foreground py-8">
                  Nenhum cliente encontrado.
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </div>

      <!-- Paginação -->
      <div v-if="patients.links" class="flex gap-2 justify-center">
        <template v-for="link in patients.links" :key="link.label">
          <Link v-if="link.url" :href="link.url"
            :class="['px-3 py-1 rounded text-sm', link.active ? 'bg-cyan-600 text-white' : 'bg-gray-200']"
            v-html="link.label" />
          <span v-else class="px-3 py-1 rounded bg-gray-100 text-gray-400 text-sm" v-html="link.label" />
        </template>
      </div>
    </div>
  </CentralAdminLayout>
</template>
