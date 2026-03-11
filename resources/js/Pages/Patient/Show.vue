<script setup>
import { Head, router } from "@inertiajs/vue3";
import TenantAdminLayout from "@/Layouts/TenantAdminLayout.vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/Components/ui/table";
import { Button } from "@/Components/ui/button";
import { RefreshCw, CheckCircle2, Clock, XCircle, ArrowLeft } from "lucide-vue-next";
import { computed } from "vue";

const props = defineProps({
  patient: { type: Object, required: true },
  smsLogs: { type: Array, default: () => [] },
});

const hasPendingOrFailed = computed(() =>
  props.smsLogs.some((l) => l.status === "pending" || l.status === "failed")
);

const statusConfig = {
  sent:    { label: "Enviado",  icon: CheckCircle2, class: "text-green-600" },
  pending: { label: "Pendente", icon: Clock,         class: "text-yellow-600" },
  failed:  { label: "Falhou",   icon: XCircle,       class: "text-red-600" },
};

const formatDate = (date) => {
  if (!date) return "-";
  return new Date(date).toLocaleDateString("pt-BR", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
};

const resendSms = () => {
  router.post(route("patients.resend-sms", props.patient.id));
};
</script>

<template>
  <Head title="Detalhes do Paciente" />

  <TenantAdminLayout>
    <div class="space-y-6">

      <div class="flex items-center gap-3">
        <Button variant="ghost" size="sm" @click="router.visit(route('patients.index'))">
          <ArrowLeft class="w-4 h-4 mr-1" />
          Voltar
        </Button>
        <h1 class="text-xl font-bold">Paciente #{{ patient.id }}</h1>
      </div>

      <!-- Dados do paciente -->
      <Card>
        <CardHeader>
          <CardTitle class="text-base">Respostas</CardTitle>
        </CardHeader>
        <CardContent>
          <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
            <div v-for="answer in patient.answers" :key="answer.id">
              <dt class="text-xs text-muted-foreground font-medium">
                {{ answer.question?.title }}
              </dt>
              <dd class="text-sm mt-0.5">{{ answer.answer || "-" }}</dd>
            </div>
          </dl>
        </CardContent>
      </Card>

      <!-- Logs de SMS -->
      <Card>
        <CardHeader class="flex flex-row items-center justify-between">
          <CardTitle class="text-base">Histórico de SMS</CardTitle>
          <Button
            v-if="hasPendingOrFailed"
            size="sm"
            variant="outline"
            @click="resendSms"
          >
            <RefreshCw class="w-4 h-4 mr-1" />
            Reenviar SMS
          </Button>
        </CardHeader>
        <CardContent class="p-0">
          <div v-if="smsLogs.length === 0" class="py-8 text-center text-sm text-muted-foreground">
            Nenhum SMS registrado para este paciente.
          </div>
          <Table v-else>
            <TableHeader>
              <TableRow>
                <TableHead class="text-center">Status</TableHead>
                <TableHead>Mensagem</TableHead>
                <TableHead class="text-center">Destinatário</TableHead>
                <TableHead class="text-center">Enviado em</TableHead>
                <TableHead class="text-center">Registrado em</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="log in smsLogs" :key="log.id">
                <TableCell class="text-center">
                  <span
                    class="inline-flex items-center gap-1 text-xs font-semibold"
                    :class="statusConfig[log.status]?.class"
                    :title="log.error_message ?? undefined"
                  >
                    <component :is="statusConfig[log.status]?.icon" class="w-4 h-4" />
                    {{ statusConfig[log.status]?.label }}
                  </span>
                </TableCell>
                <TableCell class="max-w-xs">
                  <span class="text-sm line-clamp-2" :title="log.message">
                    {{ log.message }}
                  </span>
                </TableCell>
                <TableCell class="text-center text-sm">{{ log.recipient ?? "-" }}</TableCell>
                <TableCell class="text-center text-sm">{{ formatDate(log.sent_at) }}</TableCell>
                <TableCell class="text-center text-sm">{{ formatDate(log.created_at) }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

    </div>
  </TenantAdminLayout>
</template>
