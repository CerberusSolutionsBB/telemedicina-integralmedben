<script setup>
import { Head } from "@inertiajs/vue3";
import CentralAdminLayout from "@/Layouts/CentralAdminLayout.vue";
import BarChart from "@/Components/BarChart.vue";
import DonutChart from "@/Components/DonutChart.vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import { Building2, Users, UserX, UserPlus, TrendingUp } from "lucide-vue-next";

defineProps({
  totalTenants:        { type: Number, default: 0 },
  totalPatients:       { type: Number, default: 0 },
  newThisMonth:        { type: Number, default: 0 },
  patientsWithPlan:    { type: Number, default: 0 },
  patientsWithoutPlan: { type: Number, default: 0 },
  monthlyGrowth:       { type: Array,  default: () => [] },
  tenantRanking:       { type: Array,  default: () => [] },
  planDistribution:    { type: Array,  default: () => [] },
});
</script>

<template>
  <Head title="Dashboard" />

  <CentralAdminLayout>
    <div class="space-y-6">

      <!-- Cards principais -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <Card class="border-l-4 border-l-cyan-500">
          <CardHeader class="flex flex-row items-center justify-between pb-2">
            <CardTitle class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Parceiros Ativos</CardTitle>
            <Building2 class="w-4 h-4 text-cyan-500" />
          </CardHeader>
          <CardContent>
            <div class="text-3xl font-bold text-cyan-600">{{ totalTenants }}</div>
            <p class="text-xs text-muted-foreground mt-1">credenciados</p>
          </CardContent>
        </Card>

        <Card class="border-l-4 border-l-indigo-500">
          <CardHeader class="flex flex-row items-center justify-between pb-2">
            <CardTitle class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Vidas Cadastradas</CardTitle>
            <Users class="w-4 h-4 text-indigo-500" />
          </CardHeader>
          <CardContent>
            <div class="text-3xl font-bold text-indigo-600">{{ totalPatients.toLocaleString('pt-BR') }}</div>
            <p class="text-xs text-muted-foreground mt-1">total acumulado</p>
          </CardContent>
        </Card>

        <Card class="border-l-4 border-l-amber-500">
          <CardHeader class="flex flex-row items-center justify-between pb-2">
            <CardTitle class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Sem Plano</CardTitle>
            <UserX class="w-4 h-4 text-amber-500" />
          </CardHeader>
          <CardContent>
            <div class="text-3xl font-bold text-amber-600">{{ patientsWithoutPlan.toLocaleString('pt-BR') }}</div>
            <p class="text-xs text-muted-foreground mt-1">só clube de benefícios</p>
          </CardContent>
        </Card>

        <Card class="border-l-4 border-l-emerald-500">
          <CardHeader class="flex flex-row items-center justify-between pb-2">
            <CardTitle class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Novos este mês</CardTitle>
            <UserPlus class="w-4 h-4 text-emerald-500" />
          </CardHeader>
          <CardContent>
            <div class="text-3xl font-bold text-emerald-600">{{ newThisMonth.toLocaleString('pt-BR') }}</div>
            <p class="text-xs text-muted-foreground mt-1">cadastros recentes</p>
          </CardContent>
        </Card>
      </div>

      <!-- Crescimento + Ranking -->
      <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">

        <!-- Gráfico de crescimento mensal -->
        <div class="lg:col-span-3">
          <BarChart
            title="Crescimento de Cadastros"
            :description="`Pacientes registrados por mês em ${new Date().getFullYear()}`"
            :data="monthlyGrowth"
            color="bg-cyan-500"
          />
        </div>

        <!-- Ranking de parceiros -->
        <Card class="lg:col-span-2">
          <CardHeader class="pb-3">
            <CardTitle class="text-sm font-semibold">Ranking de Parceiros</CardTitle>
          </CardHeader>
          <CardContent class="p-0">
            <table class="w-full text-xs">
              <thead>
                <tr class="border-b">
                  <th class="text-left px-4 py-2 font-medium text-muted-foreground">Parceiro</th>
                  <th class="text-right px-2 py-2 font-medium text-muted-foreground">Vidas</th>
                  <th class="text-right px-2 py-2 font-medium text-muted-foreground">S/ Plano</th>
                  <th class="text-right px-4 py-2 font-medium text-muted-foreground">Cresc.</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="tenant in tenantRanking"
                  :key="tenant.id"
                  class="border-b last:border-0 hover:bg-gray-50"
                >
                  <td class="px-4 py-2 font-medium truncate max-w-[100px]" :title="tenant.name">
                    {{ tenant.name }}
                  </td>
                  <td class="text-right px-2 py-2">{{ tenant.total.toLocaleString('pt-BR') }}</td>
                  <td class="text-right px-2 py-2 text-amber-600">{{ tenant.without_plan }}</td>
                  <td class="text-right px-4 py-2">
                    <span
                      class="inline-flex items-center gap-0.5 font-semibold"
                      :class="tenant.growth >= 0 ? 'text-emerald-600' : 'text-red-500'"
                    >
                      <TrendingUp class="w-3 h-3" />
                      {{ tenant.growth }}%
                    </span>
                  </td>
                </tr>
                <tr v-if="tenantRanking.length === 0">
                  <td colspan="4" class="text-center py-4 text-muted-foreground">Sem dados</td>
                </tr>
              </tbody>
            </table>
          </CardContent>
        </Card>
      </div>

      <!-- Cadastros mensais + Distribuição de planos -->
      <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">

        <!-- Cadastros mensais por credenciado (bar chart simples) -->
        <Card class="lg:col-span-3">
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-semibold">Cadastros Mensais</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-2">
              <div
                v-for="tenant in tenantRanking.slice(0, 6)"
                :key="tenant.id"
                class="flex items-center gap-3"
              >
                <span class="text-xs text-gray-600 w-24 truncate flex-shrink-0" :title="tenant.name">
                  {{ tenant.name }}
                </span>
                <div class="flex-1 bg-gray-100 rounded-full h-2">
                  <div
                    class="bg-cyan-500 h-2 rounded-full transition-all"
                    :style="{ width: tenantRanking[0]?.total > 0 ? (tenant.total / tenantRanking[0].total * 100) + '%' : '0%' }"
                  />
                </div>
                <span class="text-xs font-semibold text-gray-700 w-10 text-right">{{ tenant.total }}</span>
              </div>
              <p v-if="tenantRanking.length === 0" class="text-xs text-muted-foreground text-center py-4">
                Sem dados
              </p>
            </div>
          </CardContent>
        </Card>

        <!-- Distribuição de planos -->
        <Card class="lg:col-span-2" v-if="planDistribution.length">
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-semibold">Distribuição de Planos</CardTitle>
          </CardHeader>
          <CardContent class="flex items-center justify-center pt-2">
            <DonutChart :data="planDistribution" :size="140" />
          </CardContent>
        </Card>

        <Card class="lg:col-span-2" v-else>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-semibold">Distribuição de Planos</CardTitle>
          </CardHeader>
          <CardContent class="flex items-center justify-center h-32">
            <p class="text-xs text-muted-foreground">Nenhum plano configurado</p>
          </CardContent>
        </Card>
      </div>

      <!-- Gestão de Tenants -->
      <Card>
        <CardHeader class="pb-3">
          <CardTitle class="text-sm font-semibold">Gestão de Credenciados</CardTitle>
        </CardHeader>
        <CardContent class="p-0">
          <div class="overflow-x-auto">
            <table class="w-full text-xs">
              <thead>
                <tr class="border-b bg-gray-50">
                  <th class="text-left px-4 py-3 font-medium text-muted-foreground">Nome</th>
                  <th class="text-left px-4 py-3 font-medium text-muted-foreground">SubDomínio</th>
                  <th class="text-right px-4 py-3 font-medium text-muted-foreground">Vidas Ativas</th>
                  <th class="text-right px-4 py-3 font-medium text-muted-foreground">Com Plano</th>
                  <th class="text-right px-4 py-3 font-medium text-muted-foreground">Sem Plano</th>
                  <th class="text-center px-4 py-3 font-medium text-muted-foreground">Status</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="tenant in tenantRanking"
                  :key="tenant.id"
                  class="border-b last:border-0 hover:bg-gray-50"
                >
                  <td class="px-4 py-2.5 font-medium">{{ tenant.name }}</td>
                  <td class="px-4 py-2.5 text-muted-foreground">{{ tenant.subdomain }}</td>
                  <td class="text-right px-4 py-2.5 font-semibold">{{ tenant.total.toLocaleString('pt-BR') }}</td>
                  <td class="text-right px-4 py-2.5 text-indigo-600">{{ tenant.with_plan }}</td>
                  <td class="text-right px-4 py-2.5 text-amber-600">{{ tenant.without_plan }}</td>
                  <td class="text-center px-4 py-2.5">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                      <span class="w-1.5 h-1.5 rounded-full bg-emerald-500" />
                      Ativo
                    </span>
                  </td>
                </tr>
                <tr v-if="tenantRanking.length === 0">
                  <td colspan="6" class="text-center py-6 text-muted-foreground">
                    Nenhum credenciado encontrado.
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
