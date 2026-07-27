<script setup>
import { Head, router, useForm } from "@inertiajs/vue3";
import CentralAdminLayout from "@/Layouts/CentralAdminLayout.vue";
import PaginationSimple from "@/Components/PaginationSimple.vue";
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from "@/Components/ui/table";
import {
  Dialog, DialogContent, DialogHeader, DialogTitle,
} from "@/Components/ui/dialog";
import ConfirmResendSmsDialog from "@/Components/ConfirmResendSmsDialog.vue";
import SearchInput from "@/Components/SearchInput.vue";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { PlusCircle, Wallet, RefreshCw } from "lucide-vue-next";
import { ref, watch } from "vue";
import { showToast } from "@/Utils/toast";

const props = defineProps({
  logs:          { type: Object, required: true },
  quotaLogs:     { type: Array,  default: () => [] },
  globalBalance: { type: Number, default: 0 },
  globalLogs:    { type: Array,  default: () => [] },
  tenants:       { type: Array,  default: () => [] },
  filters:       { type: Object, default: () => ({}) },
});

const tenantId = ref(props.filters.tenant_id ?? "");
const status   = ref(props.filters.status ?? "");
const search   = ref(props.filters.search ?? "");

const applyFilters = () => {
  router.get(
    route("admin.sms-logs.index"),
    {
      tenant_id: tenantId.value || undefined,
      status: status.value || undefined,
      search: search.value || undefined,
    },
    { preserveScroll: true, replace: true }
  );
};

watch([tenantId, status], applyFilters);

// Recarga global
const rechargeOpen = ref(false);
const rechargeForm = useForm({ amount: 1000, notes: "" });

const submitRecharge = () => {
  rechargeForm.post(route("admin.sms-logs.add-global-balance"), {
    onSuccess: () => { rechargeOpen.value = false; rechargeForm.reset(); },
  });
};

const formatDate = (date) => {
  if (!date) return "-";
  return new Date(date).toLocaleDateString("pt-BR", {
    day: "2-digit", month: "2-digit", year: "numeric",
    hour: "2-digit", minute: "2-digit",
  });
};

// Reenvio de SMS
const logToResend = ref(null);
const resendDialogOpen = ref(false);

const openResendDialog = (log) => {
  logToResend.value = log;
  resendDialogOpen.value = true;
};

const resendLog = () => {
  if (!logToResend.value) return;
  router.post(route("admin.sms-logs.resend", logToResend.value.id), {}, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: (visitedPage) => {
      const successMsg = visitedPage.props.flash?.success;
      const errorMsg = visitedPage.props.flash?.error;
      if (successMsg) showToast(successMsg, "success");
      else if (errorMsg) showToast(errorMsg, "error");
    },
    onError: () => showToast("Erro ao reenviar SMS.", "error"),
  });
};
</script>

<template>
  <Head title="Logs de SMS" />

  <CentralAdminLayout>
    <div class="space-y-6">
      <h1 class="text-xl font-bold">Logs de SMS</h1>

      <!-- Saldo Global -->
      <div class="bg-white border rounded-xl border-gray-200 shadow-sm p-5 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-full flex items-center justify-center"
               :class="globalBalance === 0 ? 'bg-red-100' : globalBalance <= 100 ? 'bg-yellow-100' : 'bg-cyan-100'">
            <Wallet class="w-6 h-6"
                    :class="globalBalance === 0 ? 'text-red-600' : globalBalance <= 100 ? 'text-yellow-600' : 'text-cyan-600'" />
          </div>
          <div>
            <p class="text-sm text-muted-foreground font-medium">Saldo Global de SMS</p>
            <p class="text-3xl font-bold"
               :class="globalBalance === 0 ? 'text-red-600' : globalBalance <= 100 ? 'text-yellow-600' : 'text-gray-900'">
              {{ globalBalance.toLocaleString('pt-BR') }}
            </p>
            <p v-if="globalBalance === 0" class="text-xs text-red-500 mt-0.5">
              Saldo esgotado — não é possível distribuir SMS aos credenciados.
            </p>
            <p v-else-if="globalBalance <= 100" class="text-xs text-yellow-600 mt-0.5">
              Saldo baixo — considere recarregar em breve.
            </p>
          </div>
        </div>
        <Button @click="rechargeOpen = true" variant="outline" class="gap-2">
          <PlusCircle class="w-4 h-4" />
          Adicionar SMS
        </Button>
      </div>

      <!-- Filtros -->
      <div class="flex flex-wrap items-center gap-3">
        <SearchInput v-model="search" placeholder="Buscar por paciente, destinatário ou mensagem..." width="w-full sm:w-80" @search="applyFilters" />
        <select v-model="tenantId"
          class="border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500">
          <option value="">Todos os credenciados</option>
          <option v-for="tenant in tenants" :key="tenant.id" :value="tenant.id">{{ tenant.name }}</option>
        </select>
        <select v-model="status"
          class="border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500">
          <option value="">Todos os status</option>
          <option value="sent">Enviado</option>
          <option value="pending">Pendente</option>
          <option value="failed">Falhou</option>
        </select>
      </div>

      <!-- Envios -->
      <div class="bg-white border rounded-xl border-gray-200 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100">
          <h2 class="text-sm font-semibold text-gray-700">Histórico de Envios</h2>
        </div>
        <div class="overflow-x-auto">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="text-center">Id</TableHead>
                <TableHead class="text-center">Credenciado</TableHead>
                <TableHead class="text-center">Paciente</TableHead>
                <TableHead>Mensagem</TableHead>
                <TableHead class="text-center">Destinatário</TableHead>
                <TableHead class="text-center">Enviado em</TableHead>
                <TableHead class="text-center">Registrado em</TableHead>
                <TableHead class="text-center">Ações</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="log in logs.data" :key="log.id">
                <TableCell class="text-center text-sm text-muted-foreground">{{ log.id }}</TableCell>
                <TableCell class="text-center text-sm font-medium">{{ log.tenant_descricao ?? log.tenant_id ?? "-" }}</TableCell>
                <TableCell class="text-center text-sm">{{ log.patient_nome ?? log.patient_id ?? "-" }}</TableCell>
                <TableCell class="max-w-xs">
                  <span class="text-sm line-clamp-2" :title="log.message">{{ log.message }}</span>
                </TableCell>
                <TableCell class="text-center text-sm">{{ log.recipient ?? "-" }}</TableCell>
                <TableCell class="text-center text-sm">{{ formatDate(log.sent_at) }}</TableCell>
                <TableCell class="text-center text-sm">{{ formatDate(log.created_at) }}</TableCell>
                <TableCell class="text-center">
                  <Button size="sm" variant="outline" @click="openResendDialog(log)">
                    <RefreshCw class="w-4 h-4 mr-1" />
                    Reenviar
                  </Button>
                </TableCell>
              </TableRow>
              <TableRow v-if="logs.data.length === 0">
                <TableCell colspan="8" class="text-center text-sm text-muted-foreground py-8">Nenhum log encontrado.</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <div class="border-t border-gray-100 bg-gray-50/50 px-4 py-3">
          <PaginationSimple :data="logs" :links="logs.links || []"
            :has-data="(logs.data?.length ?? 0) > 0" label="logs" />
        </div>
      </div>

      <!-- Histórico do pool global -->
      <div class="bg-white border rounded-xl border-gray-200 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-2">
          <Wallet class="w-4 h-4 text-cyan-600" />
          <h2 class="text-sm font-semibold text-gray-700">Movimentações do Pool Global</h2>
        </div>
        <div class="overflow-x-auto">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="text-center">Tipo</TableHead>
                <TableHead class="text-center">Quantidade</TableHead>
                <TableHead class="text-center">Credenciado</TableHead>
                <TableHead class="text-center">Saldo após</TableHead>
                <TableHead class="text-center">Por</TableHead>
                <TableHead>Observação</TableHead>
                <TableHead class="text-center">Data</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="gl in globalLogs" :key="gl.id">
                <TableCell class="text-center">
                  <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                        :class="gl.type === 'recharge' ? 'bg-cyan-100 text-cyan-700' : 'bg-orange-100 text-orange-700'">
                    {{ gl.type === 'recharge' ? 'Recarga' : 'Distribuição' }}
                  </span>
                </TableCell>
                <TableCell class="text-center font-semibold"
                           :class="gl.amount > 0 ? 'text-green-600' : 'text-red-600'">
                  {{ gl.amount > 0 ? '+' : '' }}{{ gl.amount }}
                </TableCell>
                <TableCell class="text-center text-sm">{{ gl.tenant_id ?? "-" }}</TableCell>
                <TableCell class="text-center text-sm font-medium">{{ gl.balance_after.toLocaleString('pt-BR') }}</TableCell>
                <TableCell class="text-center text-sm">{{ gl.added_by?.name ?? "-" }}</TableCell>
                <TableCell class="text-sm text-muted-foreground">{{ gl.notes ?? "-" }}</TableCell>
                <TableCell class="text-center text-sm">{{ formatDate(gl.created_at) }}</TableCell>
              </TableRow>
              <TableRow v-if="globalLogs.length === 0">
                <TableCell colspan="7" class="text-center text-sm text-muted-foreground py-6">Nenhuma movimentação.</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </div>

      <!-- Acréscimos por tenant -->
      <div class="bg-white border rounded-xl border-gray-200 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-2">
          <PlusCircle class="w-4 h-4 text-cyan-600" />
          <h2 class="text-sm font-semibold text-gray-700">Histórico de Acréscimos por Credenciado</h2>
        </div>
        <div class="overflow-x-auto">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="text-center">Credenciado</TableHead>
                <TableHead class="text-center">Quantidade</TableHead>
                <TableHead class="text-center">Adicionado por</TableHead>
                <TableHead>Observação</TableHead>
                <TableHead class="text-center">Data</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="ql in quotaLogs" :key="ql.id">
                <TableCell class="text-center text-sm font-medium">{{ ql.tenant_id }}</TableCell>
                <TableCell class="text-center">
                  <span class="inline-flex items-center gap-1 text-sm font-semibold text-cyan-700">
                    <PlusCircle class="w-3.5 h-3.5" />{{ ql.amount }}
                  </span>
                </TableCell>
                <TableCell class="text-center text-sm">{{ ql.added_by?.name ?? "-" }}</TableCell>
                <TableCell class="text-sm text-muted-foreground">{{ ql.notes ?? "-" }}</TableCell>
                <TableCell class="text-center text-sm">{{ formatDate(ql.created_at) }}</TableCell>
              </TableRow>
              <TableRow v-if="quotaLogs.length === 0">
                <TableCell colspan="5" class="text-center text-sm text-muted-foreground py-6">Nenhum acréscimo registrado.</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </div>
    </div>

    <!-- Dialog de recarga global -->
    <Dialog :open="rechargeOpen" @update:open="rechargeOpen = false">
      <DialogContent class="sm:max-w-sm">
        <DialogHeader>
          <DialogTitle>Adicionar SMS ao Pool Global</DialogTitle>
        </DialogHeader>
        <form @submit.prevent="submitRecharge" class="space-y-4 pt-2">
          <div class="space-y-1">
            <Label for="recharge-amount">Quantidade de SMS</Label>
            <Input id="recharge-amount" v-model.number="rechargeForm.amount"
                   type="number" min="1" max="1000000" required />
            <p v-if="rechargeForm.errors.amount" class="text-sm text-red-500">{{ rechargeForm.errors.amount }}</p>
          </div>
          <div class="space-y-1">
            <Label for="recharge-notes">Observação (opcional)</Label>
            <Input id="recharge-notes" v-model="rechargeForm.notes"
                   type="text" placeholder="Ex: pacote comprado em março..." />
          </div>
          <div class="flex justify-end gap-2 pt-1">
            <Button type="button" variant="secondary" @click="rechargeOpen = false">Cancelar</Button>
            <Button type="submit" :disabled="rechargeForm.processing">Confirmar</Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>

    <ConfirmResendSmsDialog
      v-model:open="resendDialogOpen"
      :recipient="logToResend?.recipient"
      :tenant-id="logToResend?.tenant_id"
      @confirm="resendLog"
    />
  </CentralAdminLayout>
</template>
