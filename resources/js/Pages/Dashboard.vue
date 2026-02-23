<script setup>
import { Head } from "@inertiajs/vue3";
import CentralAdminLayout from "@/Layouts/CentralAdminLayout.vue";
import BarChart from "@/Components/BarChart.vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import { Building2, Users } from "lucide-vue-next";

defineProps({
  totalTenants:  { type: Number, default: 0 },
  totalPatients: { type: Number, default: 0 },
  monthlyGrowth: { type: Array,  default: () => [] },
});
</script>

<template>
  <Head title="Dashboard" />

  <CentralAdminLayout>
    <div class="grid grid-cols-1 gap-6">

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground">Parceiros Ativos</CardTitle>
            <Building2 class="w-4 h-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-3xl font-bold">{{ totalTenants }}</div>
            <p class="text-xs text-muted-foreground mt-1">credenciados cadastrados</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground">Total de Pacientes</CardTitle>
            <Users class="w-4 h-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-3xl font-bold">{{ totalPatients }}</div>
            <p class="text-xs text-muted-foreground mt-1">pacientes registrados</p>
          </CardContent>
        </Card>
      </div>

      <BarChart
        title="Crescimento de Cadastros"
        :description="`Pacientes registrados por mês em ${new Date().getFullYear()}`"
        :data="monthlyGrowth"
      />

    </div>
  </CentralAdminLayout>
</template>
