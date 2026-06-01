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
import AppSwitch from "@/Components/ui/switch/Switch.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import { RefreshCw, CheckCircle2, Clock, XCircle, MapPin, Users } from "lucide-vue-next";
import { computed } from "vue";

const props = defineProps({
  patient: { type: Object, required: true },
  smsLogs: { type: Array, default: () => [] },
});

const breadcrumbs = computed(() => [
  { label: "Pacientes", href: route("patients.index"), icon: Users },
  { label: `Detalhes do Paciente #${props.patient.id}`, href: null },
]);

const isActive = computed(() => Boolean(props.patient.status));

const hasPendingOrFailed = computed(() =>
  props.smsLogs.some((l) => l.status === "pending" || l.status === "failed")
);

const enderecoFormatado = computed(() => {
  const e = props.patient.enderecos;
  if (!e) return null;
  const partes = [];
  if (e.logradouro) {
    let linha = e.logradouro;
    if (e.numero) linha += `, ${e.numero}`;
    if (e.complemento) linha += ` - ${e.complemento}`;
    partes.push(linha);
  }
  if (e.bairro) partes.push(e.bairro);
  const cidadeEstado = [];
  if (e.cidade) cidadeEstado.push(e.cidade);
  if (e.estado) cidadeEstado.push(e.estado);
  if (cidadeEstado.length) partes.push(cidadeEstado.join("/"));
  if (e.cep) partes.push(`CEP: ${e.cep}`);
  return partes.length ? partes.join(", ") : null;
});

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

const formatCpf = (cpf) => {
  if (!cpf) return "-";
  const cleaned = cpf.replace(/\D/g, "");
  if (cleaned.length !== 11) return cpf;
  return cleaned.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
};

const resendSms = () => {
  router.post(route("patients.resend-sms", props.patient.id));
};

const toggleStatus = () => {
  router.patch(route("patients.toggle-status", props.patient.id), {}, {
    preserveScroll: true,
    preserveState: true,
  });
};
</script>

<template>
  <Head title="Detalhes do Paciente" />

  <TenantAdminLayout>
    <div class="space-y-6">

      <Breadcrumb :items="breadcrumbs" />

      <div class="flex items-center gap-3">
        <h1 class="text-xl font-bold">Detalhes do Paciente #{{ patient.id }}</h1>
      </div>

      <!-- Dados do paciente -->
      <Card>
        <CardHeader>
          <CardTitle class="text-base flex items-center justify-between">
            <span>Dados do Paciente</span>
            <div class="flex items-center gap-2">
              <AppSwitch :model-value="isActive" @update:model-value="toggleStatus" />
              <span class="text-sm font-medium" :class="isActive ? 'text-green-700' : 'text-red-700'">
                {{ isActive ? 'Ativo' : 'Inativo' }}
              </span>
            </div>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
            <div>
              <dt class="text-xs text-muted-foreground font-medium">Nome</dt>
              <dd class="text-sm mt-0.5">{{ patient.nome || '-' }}</dd>
            </div>
            <div>
              <dt class="text-xs text-muted-foreground font-medium">CPF</dt>
              <dd class="text-sm mt-0.5">{{ formatCpf(patient.cpf) }}</dd>
            </div>
            <div>
              <dt class="text-xs text-muted-foreground font-medium">Email</dt>
              <dd class="text-sm mt-0.5">{{ patient.email || '-' }}</dd>
            </div>
            <div>
              <dt class="text-xs text-muted-foreground font-medium">Sexo</dt>
              <dd class="text-sm mt-0.5">{{ patient.sexo || '-' }}</dd>
            </div>
            <div>
              <dt class="text-xs text-muted-foreground font-medium">Data de Nascimento</dt>
              <dd class="text-sm mt-0.5">{{ patient.data_nascimento ? formatDate(patient.data_nascimento) : '-' }}</dd>
            </div>
            <div>
              <dt class="text-xs text-muted-foreground font-medium">RG</dt>
              <dd class="text-sm mt-0.5">{{ patient.rg || '-' }}</dd>
            </div>
          </dl>
        </CardContent>
      </Card>

      <!-- Respostas dos formulários -->
      <Card v-if="patient.answers && patient.answers.length">
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

      <!-- Endereço -->
      <Card v-if="enderecoFormatado">
        <CardHeader>
          <CardTitle class="text-base flex items-center gap-2">
            <MapPin class="w-4 h-4 text-cyan-600" />
            Endereço
          </CardTitle>
        </CardHeader>
        <CardContent>
          <p class="text-sm text-gray-700">{{ enderecoFormatado }}</p>
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
