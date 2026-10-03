<script setup>
import { computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import TenantAdminLayout from "@/Layouts/TenantAdminLayout.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import AppSwitch from "@/Components/ui/switch/Switch.vue";
import ConfirmResendSmsDialog from "@/Components/ConfirmResendSmsDialog.vue";
import PatientSecao from "@/Components/Patient/PatientSecao.vue";
import PatientCampo from "@/Components/Patient/PatientCampo.vue";
import { usePatientShow } from "@/Composables/Patient/usePatientShow";
import { usePermissoesBeneficiario } from "@/Composables/Patient/usePermissoesBeneficiario";
import { formatarCpf, formatarDataHora } from "@/Composables/Patient/formatadores";
import {
    CheckCircle2,
    ClipboardList,
    Clock,
    FileText,
    HeartPulse,
    Home,
    MapPin,
    MessageSquare,
    Pencil,
    RefreshCw,
    UserRound,
    Users,
    XCircle,
} from "lucide-vue-next";

const props = defineProps({
    patient: { type: Object, required: true },
    smsLogs: { type: Array, default: () => [] },
    // { plano: { plano, siprov, origem, usuario, data_hora } | null, cadastro: { usuario, data_hora, auditado } }
    registro: { type: Object, default: () => ({ plano: null, cadastro: {} }) },
    // Membros da família do plano familiar: [{ id, nome, cpf, data_nascimento, tipo }]
    familiares: { type: Array, default: () => [] },
});

const {
    isActive,
    enderecoFormatado,
    cpfFormatado,
    sexoLabel,
    registradoPor,
    criadoPor,
    logToResend,
    resendLogDialogOpen,
    openResendLogDialog,
    resendLog,
    toggleStatus,
} = usePatientShow(props);

const { podeEditar, podeAlterarStatus } = usePermissoesBeneficiario();

const nome = computed(() => props.patient.nome || `Beneficiário #${props.patient.id}`);
const respostas = computed(() => props.patient.answers ?? []);

// Mesmo padrão do Controle de Acesso: "Início" › seção › registro atual.
const breadcrumbs = computed(() => [
    { label: "Início", href: route("patients.index"), icon: Home },
    { label: "Beneficiários", href: route("patients.index") },
    { label: nome.value, href: null },
]);

// Ícone, cor e texto por status do SMS (cor nunca sozinha: sempre com texto).
const statusSms = {
    sent: { label: "Enviado", icon: CheckCircle2, class: "text-green-700" },
    pending: { label: "Pendente", icon: Clock, class: "text-amber-700" },
    failed: { label: "Falhou", icon: XCircle, class: "text-red-700" },
};

const botao = "inline-flex h-10 items-center gap-2 rounded-lg px-4 text-sm font-semibold";
</script>

<template>
    <Head :title="nome" />

    <TenantAdminLayout>
        <Breadcrumb :items="breadcrumbs" />

        <div class="w-full space-y-6">
            <!-- Resumo: o essencial em uma olhada -->
            <section class="w-full rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <h1 class="break-words text-xl font-bold text-gray-900 sm:text-2xl">{{ nome }}</h1>
                        <p class="mt-1 text-sm text-gray-500">
                            CPF {{ cpfFormatado || "não informado" }}
                            <template v-if="patient.data_nascimento_formatada">
                                · Nascimento {{ patient.data_nascimento_formatada }}
                            </template>
                        </p>

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-sm font-medium"
                                :class="isActive ? 'border-green-200 bg-green-50 text-green-700' : 'border-red-200 bg-red-50 text-red-700'">
                                <component :is="isActive ? CheckCircle2 : XCircle" class="h-4 w-4" aria-hidden="true" />
                                {{ isActive ? "Ativo" : "Inativo" }}
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-sm font-medium"
                                :class="registro.plano ? 'border-cyan-200 bg-cyan-50 text-cyan-700' : 'border-gray-200 bg-gray-50 text-gray-600'">
                                <HeartPulse class="h-4 w-4" aria-hidden="true" />
                                {{ registro.plano?.plano || "Sem plano" }}
                            </span>
                            <span v-if="familiares.length"
                                class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-sm font-medium text-gray-700">
                                <Users class="h-4 w-4" aria-hidden="true" />
                                {{ familiares.length }} familiar{{ familiares.length !== 1 ? "es" : "" }}
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <label v-if="podeAlterarStatus"
                            class="mr-2 flex items-center gap-2 text-sm font-medium text-gray-700">
                            <AppSwitch :model-value="isActive" sr-text="Ativar ou inativar beneficiário"
                                @update:model-value="toggleStatus" />
                            {{ isActive ? "Ativo" : "Inativo" }}
                        </label>
                        <a :href="route('patients.pdf', patient.id)" target="_blank"
                            :class="[botao, 'border border-gray-300 text-gray-700 hover:bg-gray-50']">
                            <FileText class="h-4 w-4" />
                            Ficha em PDF
                        </a>
                        <Link v-if="podeEditar" :href="route('patients.edit', patient.id)"
                            :class="[botao, 'bg-cyan-600 text-white shadow-sm hover:bg-cyan-700']">
                            <Pencil class="h-4 w-4" />
                            Editar
                        </Link>
                    </div>
                </div>
            </section>

            <div class="grid w-full grid-cols-1 gap-6 xl:grid-cols-2">
                <PatientSecao titulo="Dados pessoais" :icone="UserRound">
                    <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                        <PatientCampo rotulo="Nome" :valor="patient.nome" class="sm:col-span-2" />
                        <PatientCampo rotulo="CPF" :valor="cpfFormatado" />
                        <PatientCampo rotulo="RG" :valor="patient.rg" />
                        <PatientCampo rotulo="Data de nascimento" :valor="patient.data_nascimento_formatada" />
                        <PatientCampo rotulo="Sexo" :valor="sexoLabel" />
                    </dl>
                </PatientSecao>

                <PatientSecao titulo="Contato e endereço" :icone="MapPin">
                    <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                        <PatientCampo rotulo="E-mail" :valor="patient.email" />
                        <PatientCampo rotulo="Celular" :valor="patient.numero" />
                        <PatientCampo rotulo="Endereço" :valor="enderecoFormatado" class="sm:col-span-2" />
                    </dl>
                </PatientSecao>

                <PatientSecao titulo="Plano" :icone="HeartPulse">
                    <dl v-if="registro.plano" class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                        <PatientCampo rotulo="Plano" class="sm:col-span-2">
                            <span class="flex flex-wrap items-center gap-2 font-medium">
                                {{ registro.plano.plano }}
                                <span class="rounded-full border px-2 py-0.5 text-xs font-medium"
                                    :class="registro.plano.siprov ? 'border-cyan-200 bg-cyan-50 text-cyan-700' : 'border-purple-200 bg-purple-50 text-purple-700'">
                                    {{ registro.plano.siprov ? "SIPROV" : "Próprio do sistema" }}
                                </span>
                            </span>
                        </PatientCampo>
                        <PatientCampo rotulo="Registrado por" :valor="registradoPor" />
                        <PatientCampo rotulo="Data e horário" :valor="registro.plano.data_hora" />
                        <PatientCampo rotulo="Origem" :valor="registro.plano.origem" />
                    </dl>
                    <p v-else class="text-base text-gray-500">Este beneficiário não tem plano vinculado.</p>
                </PatientSecao>

                <PatientSecao titulo="Cadastro" :icone="ClipboardList">
                    <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                        <PatientCampo rotulo="Criado por" :valor="criadoPor" />
                        <PatientCampo rotulo="Data e horário" :valor="registro.cadastro.data_hora" />
                    </dl>
                    <p v-if="!registro.cadastro.auditado" class="mt-3 text-sm text-gray-500">
                        Cadastro anterior à auditoria: o usuário que criou não foi registrado.
                    </p>
                </PatientSecao>
            </div>

            <PatientSecao v-if="familiares.length" titulo="Membros da família" :icone="Users" :contagem="familiares.length">
                <ul class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                    <li v-for="familiar in familiares" :key="familiar.id" class="rounded-lg border border-gray-200 p-4">
                        <p class="font-semibold text-gray-900">{{ familiar.nome }}</p>
                        <p class="text-sm font-medium text-cyan-700">{{ familiar.tipo }}</p>
                        <dl class="mt-3 grid grid-cols-2 gap-3">
                            <PatientCampo rotulo="CPF" :valor="familiar.cpf ? formatarCpf(familiar.cpf) : null" />
                            <PatientCampo rotulo="Nascimento" :valor="familiar.data_nascimento" />
                        </dl>
                    </li>
                </ul>
            </PatientSecao>

            <PatientSecao v-if="respostas.length" titulo="Respostas do formulário" :icone="ClipboardList"
                :contagem="respostas.length">
                <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2 xl:grid-cols-3">
                    <PatientCampo v-for="answer in respostas" :key="answer.id" :rotulo="answer.question?.title || 'Pergunta'"
                        :valor="answer.answer" />
                </dl>
            </PatientSecao>

            <PatientSecao titulo="Histórico de SMS" :icone="MessageSquare" :contagem="smsLogs.length">
                <p v-if="!smsLogs.length" class="text-base text-gray-500">Nenhum SMS enviado para este beneficiário.</p>

                <ul v-else class="divide-y divide-gray-100">
                    <li v-for="log in smsLogs" :key="log.id"
                        class="flex flex-col gap-3 py-4 first:pt-0 last:pb-0 md:flex-row md:items-center md:justify-between">
                        <div class="min-w-0 space-y-1">
                            <p class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                                <span class="inline-flex items-center gap-1 font-semibold" :class="statusSms[log.status]?.class">
                                    <component :is="statusSms[log.status]?.icon" class="h-4 w-4" aria-hidden="true" />
                                    {{ statusSms[log.status]?.label ?? log.status }}
                                </span>
                                <span class="text-gray-500">Para {{ log.recipient ?? "-" }}</span>
                                <span class="text-gray-500">Enviado em {{ formatarDataHora(log.sent_at) }}</span>
                            </p>
                            <p class="break-words text-base text-gray-800">{{ log.message }}</p>
                            <p v-if="log.status === 'failed' && log.error_message" class="text-sm text-red-700">
                                Motivo: {{ log.error_message }}
                            </p>
                        </div>
                        <button type="button" @click="openResendLogDialog(log)"
                            :class="[botao, 'shrink-0 border border-gray-300 text-gray-700 hover:bg-gray-50']">
                            <RefreshCw class="h-4 w-4" />
                            Reenviar
                        </button>
                    </li>
                </ul>
            </PatientSecao>
        </div>

        <ConfirmResendSmsDialog v-model:open="resendLogDialogOpen" :recipient="logToResend?.recipient"
            @confirm="resendLog" />
    </TenantAdminLayout>
</template>
