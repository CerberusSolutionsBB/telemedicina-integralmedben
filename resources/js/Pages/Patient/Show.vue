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
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs";
import ConfirmResendSmsDialog from "@/Components/ConfirmResendSmsDialog.vue";
import AppSwitch from "@/Components/ui/switch/Switch.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import { RefreshCw, CheckCircle2, Clock, XCircle, MapPin, Users } from "lucide-vue-next";
import { computed, ref } from "vue";
import { showToast } from "@/Utils/toast";

const props = defineProps({
  patient: { type: Object, required: true },
  smsLogs: { type: Array, default: () => [] },
});

const showFlashToast = (visitedPage) => {
  const successMsg = visitedPage.props.flash?.success;
  const errorMsg = visitedPage.props.flash?.error;
  if (successMsg) showToast(successMsg, "success");
  else if (errorMsg) showToast(errorMsg, "error");
};

const breadcrumbs = computed(() => [
  { label: "Pacientes", href: route("patients.index"), icon: Users },
  { label: `Detalhes do Paciente #${props.patient.id}`, href: null },
]);

const isActive = computed(() => Boolean(props.patient.status));

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

const logToResend = ref(null);
const resendLogDialogOpen = ref(false);

const openResendLogDialog = (log) => {
  logToResend.value = log;
  resendLogDialogOpen.value = true;
};

const resendLog = () => {
  if (!logToResend.value) return;
  router.post(route("patients.sms-logs.resend", [props.patient.id, logToResend.value.id]), {}, {
    preserveScroll: true,
    onSuccess: showFlashToast,
    onError: () => showToast("Erro ao reenviar SMS.", "error"),
  });
};

const toggleStatus = () => {
  router.patch(route("patients.toggle-status", props.patient.id), {}, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: showFlashToast,
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

      <Tabs default-value="dados">
        <TabsList>
          <TabsTrigger value="dados">Dados</TabsTrigger>
          <TabsTrigger v-if="patient.answers && patient.answers.length" value="respostas">Respostas</TabsTrigger>
          <TabsTrigger v-if="enderecoFormatado" value="endereco">Endereço</TabsTrigger>
          <TabsTrigger value="sms">SMS</TabsTrigger>
        </TabsList>

        <TabsContent value="dados">
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
                  <dd class="text-sm mt-0.5">{{ patient.data_nascimento_formatada || '-' }}</dd>
                </div>
                <div>
                  <dt class="text-xs text-muted-foreground font-medium">RG</dt>
                  <dd class="text-sm mt-0.5">{{ patient.rg || '-' }}</dd>
                </div>
              </dl>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent v-if="patient.answers && patient.answers.length" value="respostas">
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
        </TabsContent>

        <TabsContent v-if="enderecoFormatado" value="endereco">
          <Card>
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
        </TabsContent>

        <TabsContent value="sms">
          <Card>
            <CardHeader>
              <CardTitle class="text-base">Histórico de SMS</CardTitle>
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
                    <TableHead class="text-center">Ações</TableHead>
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
                    <TableCell class="text-center">
                      <Button
                        size="sm"
                        variant="outline"
                        @click="openResendLogDialog(log)"
                      >
                        <RefreshCw class="w-4 h-4 mr-1" />
                        Reenviar
                      </Button>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>

      <ConfirmResendSmsDialog
        v-model:open="resendLogDialogOpen"
        :recipient="logToResend?.recipient"
        @confirm="resendLog"
      />

    </div>
  </TenantAdminLayout>
</template>
