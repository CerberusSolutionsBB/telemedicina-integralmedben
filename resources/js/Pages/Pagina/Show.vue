<script setup>
import CentralAdminLayout from '@/Layouts/CentralAdminLayout.vue'
import { router } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import ActionDropdown from '@/Components/ActionDropdown.vue'
import PomponeteLink from '@/Components/PomponeteLink.vue'
import FormSelectorDialog from '@/Components/FormSelectorDialog.vue'
import UserBadge from '@/Components/UserBadge.vue'
import Breadcrumb from '@/Components/Breadcrumb.vue'
import { Button } from '@/Components/ui/button'
import { Label } from '@/Components/ui/label'
import FormLinkedCard from '@/Components/Cards/FormLinkedCard.vue'
import ConfirmDeleteModal from '@/Components/ConfirmDeleteModal.vue'
import SmsTemplateModal from '@/Components/SmsTemplateModal.vue'
import EbaLogo from '@/Components/Ebas/EbaLogo.vue'
import ImageUpload from '@/Components/ImageUpload.vue'
import SearchInput from '@/Components/SearchInput.vue'
import { formatDateTime } from '@/Composables/Pagina/helpers'
import { usePaginaTenant } from '@/Composables/Pagina/usePaginaTenant'
import { usePaginaForms } from '@/Composables/Pagina/usePaginaForms'
import { usePaginaSms } from '@/Composables/Pagina/usePaginaSms'
import { usePaginaConfig } from '@/Composables/Pagina/usePaginaConfig'
import { useCartaoDinamico } from '@/Composables/Pagina/useCartaoDinamico'
import { useTelemedicina } from '@/Composables/Pagina/useTelemedicina'
import { usePaginaPlanos, quantidadeMinima } from '@/Composables/Pagina/usePaginaPlanos'
import { useAbaAtiva } from '@/Composables/Pagina/useAbaAtiva'
import { usePaginaHistorico } from '@/Composables/Pagina/usePaginaHistorico'
import {
    usePaginaPatients,
    formatCpf,
    formatDateShort,
    patientSexoIcon,
    patientSexoColor,
    registroLabel,
    registroColor,
    registroIcon,
} from '@/Composables/Pagina/usePaginaPatients'
import {
    Home,
    Building2,
    Globe,
    Calendar,
    Pencil,
    ExternalLink,
    Copy,
    FileText,
    Settings,
    Trash2,
    X,
    MessageSquare,
    Plus,
    Loader2,
    Check,
    HeartPulse,
    Search,
    CheckCircle,
    AlertCircle,
    Users,
    Info,
    ChevronLeft,
    ChevronRight,
    ImagesIcon,
    CreditCard,
    Sparkles,
    Palette,
    QrCode,
    Layers,
    Minus,
    RotateCcw
} from 'lucide-vue-next'

const props = defineProps({
    tenant: {
        type: Object,
        required: true,
    },
    forms: {
        type: Array,
        default: () => [],
    },
    fomrs_tenants: {
        type: Array,
        default: () => [],
    },
    smsTemplates: {
        type: Array,
        default: () => [],
    },
    statusFormularioDinamico: {
        type: Boolean,
        default: false,
    },
    telemedicinaEnabled: {
        type: Boolean,
        default: false,
    },
    cartaoPacienteEnabled: {
        type: Boolean,
        default: false,
    },
    cartaoDinamicoEnabled: {
        type: Boolean,
        default: false,
    },
    cartaoDinamico: {
        type: Object,
        default: () => ({}),
    },
    telemedicinaQuestions: {
        type: Array,
        default: () => [],
    },
    telemedicinaVinculados: {
        type: Array,
        default: () => [],
    },
    allTenants: {
        type: Array,
        default: () => [],
    },
    patients: {
        type: Array,
        default: () => [],
    },
    arquivos: {
        type: Array,
        default: () => [],
    },
    planos: {
        type: Array,
        default: () => [],
    },
    tenantPlanos: {
        type: Array,
        default: () => [],
    },
    planoUso: {
        type: Object,
        default: () => ({}),
    },
    planoRegistros: {
        type: Array,
        default: () => [],
    },
    planoRegistrosFiltros: {
        type: Object,
        default: () => ({}),
    },
    planoRegistrosTotais: {
        type: Object,
        default: () => ({ total: 0, planos: [] }),
    },
    planoRegistrosLimite: {
        type: Number,
        default: 100,
    },
})

const arquivosLocal = ref([...props.arquivos])

watch(() => props.arquivos, (newVal) => {
    arquivosLocal.value = [...newVal]
})

const {
    detail,
    user,
    domains,
    tenantName,
    tenantSlug,
    isGeneratingDetail,
    copyToClipboard,
    generateDetail,
} = usePaginaTenant(props)

const {
    availableForms,
    dialogOpen,
    selectedFormIds,
    isSavingForms,
    confirmDialogOpen,
    selectedFormToRemove,
    isRemoving,
    syncForms,
    handleUpdateExpiresAt,
    openRemoveLinkDialog,
    closeRemoveLinkDialog,
    confirmRemoveLink,
} = usePaginaForms(props)

const {
    smsModalOpen,
    smsModalTemplate,
    smsDeleteModal,
    smsDeleteItem,
    isDeletingSms,
    openSmsModal,
    closeSmsModal,
    deleteSmsTemplate,
    closeSmsDeleteModal,
    confirmDeleteSmsTemplate,
} = usePaginaSms()

const {
    isTogglingStatus,
    toggleStatusFormularioDinamico,
    isTogglingCartaoPaciente,
    toggleCartaoPaciente,
} = usePaginaConfig(props)

const {
    fontesCartaoDinamico,
    isTogglingCartaoDinamico,
    toggleCartaoDinamico,
    isSavingCoresCartaoDinamico,
    corPrimariaCartaoDinamico,
    corSecundariaCartaoDinamico,
    corTextoCartaoDinamico,
    fonteCartaoDinamico,
    salvarCoresCartaoDinamico,
    isSavingQrcodeCartaoDinamico,
    qrcodeHabilitadoCartaoDinamico,
    qrcodeDadosCartaoDinamico,
    versoTextoInfoCartaoDinamico,
    versoRodapeCartaoDinamico,
    salvarQrcodeCartaoDinamico,
    cartaoDinamicoAssets,
    isUploadingCartaoImagem,
    handleCartaoImagemChange,
    cartaoDinamicoSearch,
    cartaoDinamicoActiveCategory,
    cartaoDinamicoExpandedKey,
    cartaoDinamicoConfigurations,
    cartaoDinamicoAllCategories,
    cartaoDinamicoFilteredConfigurations,
    cartaoDinamicoConfigsByCategory,
    isCartaoDinamicoExpanded,
    toggleCartaoDinamicoExpand,
    clearCartaoDinamicoFiltros,
    cartaoDinamicoTypeIconClasses,
    cartaoDinamicoHighlight,
    cartaoDinamicoHasUnsavedChanges,
} = useCartaoDinamico(props)

const {
    isSavingTelemedicina,
    telemedicinaSearch,
    selectedTelemedicinaIds,
    telemedicinaUnlinkModal,
    telemedicinaUnlinkItem,
    isUnlinkingTelemedicina,
    siprovModalOpen,
    siprovSearch,
    siprovResults,
    siprovSelected,
    siprovError,
    siprovPage,
    siprovHasNext,
    siprovTotal,
    isSearchingSiprov,
    isSavingSiprov,
    filteredTelemedicinaVinculados,
    linkedTelemedicinaIds,
    syncTelemedicina,
    unlinkTelemedicina,
    closeUnlinkTelemedicina,
    confirmUnlinkTelemedicina,
    searchSiprov,
    goToSiprovPage,
    openSiprovModal,
    getSiprovKey,
    toggleSiprovItem,
    toggleSelectAllSiprov,
    vincularSiprov,
} = useTelemedicina(props)

const {
    patientSearch,
    patientPage,
    registroFiltro,
    totaisPorRegistro,
    alternarRegistro,
    filteredPatients,
    paginatedPatients,
    totalPatientPages,
    showingFrom,
    showingTo,
    patientPageLinks,
    goToPatientPage,
} = usePaginaPatients(props)

const {
    planoRows,
    isSavingPlanos,
    planosSelecionados,
    totalQuantidadePlanos,
    totalEmUsoPlanos,
    planosHasUnsavedChanges,
    isQuantidadeInvalida,
    hasQuantidadeInvalida,
    planoVagas,
    errosCotaSiprov,
    zerarModal,
    abrirZerarContagem,
    fecharZerarContagem,
    confirmarZerarContagem,
    descartarPlanos,
    salvarPlanos,
} = usePaginaPlanos(props)

const siprovCotaErros = computed(() =>
    errosCotaSiprov(siprovResults.value.filter(item => siprovSelected.value.includes(getSiprovKey(item)))),
)

const tabs = computed(() => [
    { key: 'overview', label: 'Visão geral', icon: Building2 },
    { key: 'forms', label: 'Formulários', icon: FileText, badge: props.fomrs_tenants.length },
    { key: 'sms', label: 'Templates SMS', icon: MessageSquare, badge: props.smsTemplates.length },
    { key: 'telemedicina', label: 'Telemedicina', icon: HeartPulse, badge: props.telemedicinaVinculados.length || null },
    { key: 'planos', label: 'Planos', icon: Layers, badge: props.tenantPlanos.length || null },
    { key: 'patients', label: 'Pacientes', icon: Users, badge: props.patients.length || null },
    { key: 'logo', label: 'Logos', icon: ImagesIcon, badge: null },
    { key: 'cartao-dinamico', label: 'Cartão Dinâmico', icon: Sparkles },
    { key: 'config', label: 'Configuração', icon: Settings },
])

// Aba sobrevive ao recarregar (?aba=) e aos redirects após ações (guardada por parceiro).
const { activeTab } = useAbaAtiva(`pagina:${props.tenant.id}:aba`, tabs.value.map((tab) => tab.key), 'overview')

// Sub-abas de Planos: Dados do plano | Histórico (também persistidas, em ?planos=).
const { activeTab: planosAba } = useAbaAtiva(`pagina:${props.tenant.id}:planos`, ['dados', 'historico'], 'dados', 'planos')

const {
    filtros: historicoFiltros,
    carregando: historicoCarregando,
    temFiltro: historicoTemFiltro,
    alternarPlano: alternarHistoricoPlano,
    limparFiltros: limparHistoricoFiltros,
} = usePaginaHistorico(props)

const breadcrumbs = computed(() => [
    { label: 'Página de Parceiros', href: route('pagina.index'), icon: Home },
    { label: tenantName.value, href: null },
])

// Ícone por tipo de configuração/categoria do Cartão Dinâmico: permite
// reconhecer o tipo de cada card sem precisar ler o texto.
const cartaoDinamicoTypeIcon = (config) => {
    if (config.type === 'image') return ImagesIcon
    if (config.type === 'toggle') return Sparkles
    if (config.type === 'qrcode') return QrCode
    return Palette
}

const cartaoDinamicoCategoryIconMap = {
    all: Settings,
    'Aparência': Palette,
    'Imagens': ImagesIcon,
    'Status': Sparkles,
    'QR Code': QrCode,
}

const cartaoDinamicoCategoryIcon = (category) => cartaoDinamicoCategoryIconMap[category] || Settings
</script>

<template>
    <CentralAdminLayout>
        <div v-if="isGeneratingDetail"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 backdrop-blur-sm">
            <div class="mx-4 w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl border border-gray-100">
                <div class="flex flex-col items-center text-center gap-4">
                    <span class="loading loading-spinner loading-lg text-primary"></span>

                    <div>
                        <h3 class="text-lg font-bold text-gray-900">
                            Gerando configuração
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            Aguarde enquanto os dados do tenant são preparados.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
                <Breadcrumb v-if="breadcrumbs.length" :items="breadcrumbs" class="mb-4" />

                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div class="flex items-start gap-4 w-full">
                        <div class="w-16 h-16 rounded-2xl bg-cyan-100 flex items-center justify-center shrink-0">
                            <Building2 class="w-8 h-8 text-cyan-600" />
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 truncate">
                                        {{ tenantName }}
                                    </h1>

                                    <p class="text-sm text-gray-500 mt-1 truncate">
                                        {{ tenant.tenant_domain || 'Sem domínio vinculado' }}
                                    </p>
                                </div>

                                <div class="shrink-0">
                                    <ActionDropdown>
                                        <template #default="{ close }">
                                            <button
                                                class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                @click="
                                                    router.visit(route('pagina.edit', tenant.id));
                                                close();
                                                ">
                                                <Pencil class="w-4 h-4" />
                                                Editar tenant
                                            </button>

                                            <button
                                                class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                @click="
                                                    copyToClipboard(tenant.url);
                                                close();
                                                ">
                                                <Copy class="w-4 h-4" />
                                                Copiar link
                                            </button>

                                            <a v-if="tenant.url" :href="tenant.url" target="_blank"
                                                rel="noopener noreferrer"
                                                class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                @click="close">
                                                <ExternalLink class="w-4 h-4" />
                                                Acessar tenant
                                            </a>

                                            <button v-if="!detail"
                                                class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                                                :disabled="isGeneratingDetail" @click="
                                                    generateDetail(tenant.id);
                                                close();
                                                ">
                                                <Settings class="w-4 h-4" />
                                                Gerar configuração
                                            </button>

                                            <div class="border-t border-gray-100 my-1"></div>

                                            <button
                                                class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                                                @click="close">
                                                <Trash2 class="w-4 h-4" />
                                                Excluir
                                            </button>
                                        </template>
                                    </ActionDropdown>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="border-b border-gray-100 bg-gray-50/70 px-4 md:px-6 pt-4">
                    <div class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300">
                        <div class="flex min-w-max gap-2 pb-3">
                            <button v-for="tab in tabs" :key="tab.key" type="button" :disabled="tab.disabled"
                                @click="!tab.disabled && (activeTab = tab.key)"
                                class="group flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-medium transition-all duration-200 whitespace-nowrap border"
                                :class="[
                                    activeTab === tab.key
                                        ? 'bg-white text-cyan-600 border-cyan-200 shadow-sm'
                                        : 'bg-transparent text-gray-600 border-transparent hover:bg-white hover:border-gray-200 hover:text-gray-900',
                                    tab.disabled
                                        ? 'opacity-40 cursor-not-allowed'
                                        : 'cursor-pointer'
                                ]">
                                <component :is="tab.icon" class="w-4 h-4 shrink-0"
                                    :class="activeTab === tab.key ? 'text-cyan-500' : 'text-gray-400 group-hover:text-gray-600'" />

                                <span>
                                    {{ tab.label }}
                                </span>

                                <span v-if="tab.badge !== undefined" class="badge badge-sm border-0" :class="activeTab === tab.key
                                    ? 'badge-info text-white'
                                    : 'badge-ghost text-gray-600'">
                                    {{ tab.badge }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <!-- Visão geral -->
                    <div v-if="activeTab === 'overview'" class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="text-xs uppercase tracking-wide text-gray-500">
                                Nome/Descrição
                            </label>

                            <div class="mt-1 p-3 rounded-xl border border-gray-200 bg-gray-50">
                                {{ tenantName }}
                            </div>
                        </div>

                        <div>
                            <label class="text-xs uppercase tracking-wide text-gray-500">
                                Slug
                            </label>

                            <div class="mt-1 p-3 rounded-xl border border-gray-200 bg-gray-50">
                                {{ tenantSlug }}
                            </div>
                        </div>

                        <div>
                            <label class="text-xs uppercase tracking-wide text-gray-500">
                                Banco do Tenant
                            </label>

                            <div class="mt-1 p-3 rounded-xl border border-gray-200 bg-gray-50 break-words">
                                {{ tenant.tenancy_db_name || '-' }}
                            </div>
                        </div>

                        <div>
                            <label class="text-xs uppercase tracking-wide text-gray-500">
                                Domínio Principal
                            </label>

                            <div class="mt-1 p-3 rounded-xl border border-gray-200 bg-gray-50 break-words">
                                {{ tenant.tenant_domain || '-' }}
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <label class="text-xs uppercase tracking-wide text-gray-500">
                                URL
                            </label>

                            <div
                                class="mt-1 p-3 rounded-xl border border-gray-200 bg-gray-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <PomponeteLink v-if="tenant.url" :url="tenant.url" :label="tenant.url" />

                                <span v-else>-</span>

                                <div v-if="tenant.url" class="flex gap-2">
                                    <button class="btn btn-sm btn-ghost" title="Copiar link"
                                        @click="copyToClipboard(tenant.url)">
                                        <Copy class="w-4 h-4" />
                                    </button>

                                    <a :href="tenant.url" target="_blank" rel="noopener noreferrer"
                                        class="btn btn-sm btn-ghost" title="Acessar tenant">
                                        <ExternalLink class="w-4 h-4" />
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <label class="text-xs uppercase tracking-wide text-gray-500">
                                Domínios ({{ domains.length }})
                            </label>

                            <div class="mt-1 p-3 rounded-xl border border-gray-200 bg-gray-50">
                                <ul v-if="domains.length" class="space-y-2">
                                    <li v-for="domain in domains" :key="domain.id || domain.domain"
                                        class="flex items-center gap-2 break-all">
                                        <Globe class="w-4 h-4 text-cyan-500 shrink-0" />
                                        {{ domain.domain }}
                                    </li>
                                </ul>

                                <span v-else class="text-gray-500">Nenhum domínio cadastrado.</span>
                            </div>
                        </div>

                        <div>
                            <label class="text-xs uppercase tracking-wide text-gray-500">
                                Criado em
                            </label>

                            <div class="mt-1 p-3 rounded-xl border border-gray-200 bg-gray-50 flex items-center gap-2">
                                <Calendar class="w-4 h-4 text-cyan-500" />
                                {{ formatDateTime(tenant.created_at) }}
                            </div>
                        </div>

                        <div>
                            <label class="text-xs uppercase tracking-wide text-gray-500">
                                Atualizado em
                            </label>

                            <div class="mt-1 p-3 rounded-xl border border-gray-200 bg-gray-50 flex items-center gap-2">
                                <Calendar class="w-4 h-4 text-cyan-500" />
                                {{ formatDateTime(tenant.updated_at) }}
                            </div>
                        </div>

                        <template v-if="detail">
                            <div>
                                <label class="text-xs uppercase tracking-wide text-gray-500">
                                    Código
                                </label>

                                <div class="mt-1 p-3 rounded-xl border border-gray-200 bg-gray-50">
                                    {{ detail.code || '-' }}
                                </div>
                            </div>

                            <div>
                                <label class="text-xs uppercase tracking-wide text-gray-500">
                                    Sigla
                                </label>

                                <div class="mt-1 p-3 rounded-xl border border-gray-200 bg-gray-50">
                                    {{ detail.sigla || '-' }}
                                </div>
                            </div>

                            <div>
                                <label class="text-xs uppercase tracking-wide text-gray-500">
                                    Path Arquivos
                                </label>

                                <div class="mt-1 p-3 rounded-xl border border-gray-200 bg-gray-50 break-words">
                                    {{ detail.path_arquivos || '-' }}
                                </div>
                            </div>

                            <div>
                                <label class="text-xs uppercase tracking-wide text-gray-500">
                                    Cores
                                </label>

                                <div class="mt-1 p-3 rounded-xl border border-gray-200 bg-gray-50">
                                    Primária: {{ detail.cor_primaria || '-' }} /
                                    Secundária: {{ detail.cor_secundaria || '-' }}
                                </div>
                            </div>
                        </template>

                        <div v-if="user" class="md:col-span-2">
                            <label class="text-xs uppercase tracking-wide text-gray-500">
                                Responsável
                            </label>

                            <div class="mt-1 p-3 rounded-xl border border-gray-200 bg-gray-50">
                                <UserBadge :user="user" show-email />
                            </div>
                        </div>
                    </div>

                    <!-- Formulários -->
                    <div v-if="activeTab === 'forms'" class="space-y-5">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold flex items-center gap-2">
                                    <FileText class="w-5 h-5 text-cyan-500" />
                                    Formulários Vinculados
                                </h2>

                                <p class="text-sm text-gray-500 mt-1">
                                    Gerencie os formulários associados a este tenant.
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="badge badge-info badge-outline">
                                    {{ fomrs_tenants.length }} formulário(s)
                                </span>

                                <Button v-if="detail" variant="primary" :disabled="isSavingForms"
                                    @click="dialogOpen = true">
                                    Adicionar
                                </Button>
                            </div>
                        </div>

                        <div v-if="fomrs_tenants.length" class="space-y-4">
                            <FormLinkedCard v-for="item in fomrs_tenants" :key="item.id" :item="item" :tenant="tenant"
                                @update:expiresAt="handleUpdateExpiresAt" @remove:link="openRemoveLinkDialog" />
                        </div>

                        <div v-else class="text-center py-10 text-gray-500">
                            Nenhum formulário vinculado.
                        </div>
                    </div>

                    <!-- Templates SMS -->
                    <div v-if="activeTab === 'sms'" class="space-y-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-semibold flex items-center gap-2">
                                    <MessageSquare class="w-5 h-5 text-cyan-500" />
                                    Templates SMS
                                </h2>
                                <p class="text-sm text-gray-500 mt-1">Gerencie as mensagens enviadas por SMS aos
                                    pacientes.</p>
                            </div>
                            <Button variant="primary" @click="openSmsModal()">
                                <Plus class="w-4 h-4 mr-1" />
                                Novo Template
                            </Button>
                        </div>

                        <div v-if="props.smsTemplates.length === 0"
                            class="bg-white p-12 rounded-xl border border-gray-200 text-center">
                            <div
                                class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                <MessageSquare class="w-8 h-8 text-gray-400" />
                            </div>
                            <p class="text-lg font-medium text-gray-900">Nenhum template SMS</p>
                            <p class="text-sm text-gray-500 mt-1">Crie um template para enviar mensagens automáticas aos
                                pacientes.
                            </p>
                        </div>

                        <div v-else class="space-y-4">
                            <div v-for="template in props.smsTemplates" :key="template.id"
                                class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-2">
                                            <h4 class="text-sm font-semibold text-gray-900">{{ template.name }}</h4>
                                            <span :class="['px-2 py-0.5 rounded-full text-xs font-medium',
                                                template.is_active
                                                    ? 'bg-green-100 text-green-700 border border-green-200'
                                                    : 'bg-gray-100 text-gray-500 border border-gray-200']">
                                                {{ template.is_active ? 'Ativo' : 'Inativo' }}
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-600 whitespace-pre-line">{{ template.message }}</p>
                                        <div class="flex items-center gap-3 mt-3 text-xs text-gray-400">
                                            <span>Atualizado: {{ new
                                                Date(template.updated_at).toLocaleDateString('pt-BR') }}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1 flex-shrink-0">
                                        <button @click="openSmsModal(template)"
                                            class="p-2 text-cyan-600 hover:text-cyan-800 hover:bg-cyan-50 rounded-lg transition-all"
                                            title="Editar">
                                            <Pencil class="w-4 h-4" />
                                        </button>
                                        <button @click="deleteSmsTemplate(template)"
                                            class="p-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-all"
                                            title="Remover">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Configuração -->
                    <div v-if="activeTab === 'config'" class="space-y-5">
                        <div>
                            <h2 class="text-lg font-semibold flex items-center gap-2">
                                <Settings class="w-5 h-5 text-cyan-500" />
                                Configuração
                            </h2>
                            <p class="text-sm text-gray-500 mt-1">Configure comportamentos dinâmicos e envio de SMS para
                                este
                                tenant.</p>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                            <!-- Status Formulário Dinâmico -->
                            <div
                                class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="p-3 bg-purple-50 rounded-lg">
                                            <Settings class="w-6 h-6 text-purple-600" />
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-semibold text-gray-900">Status Formulário Dinâmico
                                            </h4>
                                            <p class="text-xs text-gray-500 mt-0.5">Atualização automática do paciente
                                            </p>
                                        </div>
                                    </div>
                                    <button @click="toggleStatusFormularioDinamico" :disabled="isTogglingStatus" :class="[
                                        'relative inline-flex h-7 w-12 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2',
                                        statusFormularioDinamico ? 'bg-purple-600' : 'bg-gray-200',
                                        isTogglingStatus ? 'opacity-50 cursor-not-allowed' : ''
                                    ]">
                                        <span :class="[
                                            'pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out',
                                            statusFormularioDinamico ? 'translate-x-5' : 'translate-x-0'
                                        ]" />
                                    </button>
                                </div>
                                <p class="text-sm text-gray-500 mt-4">
                                    Quando ativado, o formulário vinculado ao paciente será atualizado dinamicamente
                                    conforme as
                                    respostas recebidas.
                                </p>
                                <div class="mt-3">
                                    <span :class="[
                                        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium',
                                        statusFormularioDinamico
                                            ? 'bg-purple-100 text-purple-700 border border-purple-200'
                                            : 'bg-gray-100 text-gray-500 border border-gray-200'
                                    ]">
                                        <span
                                            :class="['w-1.5 h-1.5 rounded-full', statusFormularioDinamico ? 'bg-purple-500' : 'bg-gray-400']" />
                                        {{ statusFormularioDinamico ? 'Ativado' : 'Desativado' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Cartão de Paciente -->
                            <div
                                class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="p-3 bg-cyan-50 rounded-lg">
                                            <CreditCard class="w-6 h-6 text-cyan-600" />
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-semibold text-gray-900">Cartão de Paciente
                                            </h4>
                                            <p class="text-xs text-gray-500 mt-0.5">Geração do cartão de benefício
                                            </p>
                                        </div>
                                    </div>
                                    <button @click="toggleCartaoPaciente" :disabled="isTogglingCartaoPaciente" :class="[
                                        'relative inline-flex h-7 w-12 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2',
                                        cartaoPacienteEnabled ? 'bg-cyan-600' : 'bg-gray-200',
                                        isTogglingCartaoPaciente ? 'opacity-50 cursor-not-allowed' : ''
                                    ]">
                                        <span :class="[
                                            'pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out',
                                            cartaoPacienteEnabled ? 'translate-x-5' : 'translate-x-0'
                                        ]" />
                                    </button>
                                </div>
                                <p class="text-sm text-gray-500 mt-4">
                                    Quando ativado, o botão "Gerar Cartão" fica disponível na listagem de pacientes
                                    deste tenant, para pacientes atuais e novos cadastros.
                                </p>
                                <div class="mt-3">
                                    <span :class="[
                                        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium',
                                        cartaoPacienteEnabled
                                            ? 'bg-cyan-100 text-cyan-700 border border-cyan-200'
                                            : 'bg-gray-100 text-gray-500 border border-gray-200'
                                    ]">
                                        <span
                                            :class="['w-1.5 h-1.5 rounded-full', cartaoPacienteEnabled ? 'bg-cyan-500' : 'bg-gray-400']" />
                                        {{ cartaoPacienteEnabled ? 'Ativado' : 'Desativado' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Cartão Dinâmico -->
                            <div
                                class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="p-3 bg-purple-50 rounded-lg">
                                            <Sparkles class="w-6 h-6 text-purple-600" />
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-semibold text-gray-900">Cartão Dinâmico
                                            </h4>
                                            <p class="text-xs text-gray-500 mt-0.5">Geração do cartão com identidade
                                                visual própria
                                            </p>
                                        </div>
                                    </div>
                                    <button @click="toggleCartaoDinamico" :disabled="isTogglingCartaoDinamico" :class="[
                                        'relative inline-flex h-7 w-12 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2',
                                        cartaoDinamicoEnabled ? 'bg-purple-600' : 'bg-gray-200',
                                        isTogglingCartaoDinamico ? 'opacity-50 cursor-not-allowed' : ''
                                    ]">
                                        <span :class="[
                                            'pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out',
                                            cartaoDinamicoEnabled ? 'translate-x-5' : 'translate-x-0'
                                        ]" />
                                    </button>
                                </div>
                                <p class="text-sm text-gray-500 mt-4">
                                    Quando ativado, o botão "Cartão Dinâmico" fica disponível na listagem de
                                    pacientes deste tenant, ao lado do "Gerar Cartão" tradicional. Configure cores,
                                    logo e imagens na aba "Cartão Dinâmico".
                                </p>
                                <div class="mt-3">
                                    <span :class="[
                                        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium',
                                        cartaoDinamicoEnabled
                                            ? 'bg-purple-100 text-purple-700 border border-purple-200'
                                            : 'bg-gray-100 text-gray-500 border border-gray-200'
                                    ]">
                                        <span
                                            :class="['w-1.5 h-1.5 rounded-full', cartaoDinamicoEnabled ? 'bg-purple-500' : 'bg-gray-400']" />
                                        {{ cartaoDinamicoEnabled ? 'Ativado' : 'Desativado' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Template SMS Vinculado -->
                            <div
                                class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="p-3 bg-green-50 rounded-lg">
                                        <MessageSquare class="w-6 h-6 text-green-600" />
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-900">Template SMS Vinculado</h4>
                                        <p class="text-xs text-gray-500 mt-0.5">Mensagem enviada ao paciente</p>
                                    </div>
                                </div>
                                <div v-if="props.smsTemplates.length === 0"
                                    class="bg-gray-50 p-6 rounded-lg border border-dashed border-gray-300 text-center">
                                    <MessageSquare class="w-8 h-8 mx-auto text-gray-400 mb-2" />
                                    <p class="text-sm text-gray-500">Nenhum template disponível.</p>
                                    <p class="text-xs text-gray-400 mt-1">Crie na aba "Templates SMS".</p>
                                </div>
                                <div v-else class="space-y-2">
                                    <div v-for="template in props.smsTemplates" :key="template.id"
                                        class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 bg-white">
                                        <div
                                            :class="['w-3 h-3 rounded-full shrink-0', template.is_active ? 'bg-green-500' : 'bg-gray-300']" />
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <p class="text-sm font-medium text-gray-900">{{ template.name }}</p>
                                                <span :class="['px-1.5 py-0.5 rounded text-xs font-medium',
                                                    template.is_active
                                                        ? 'bg-green-100 text-green-700'
                                                        : 'bg-gray-100 text-gray-500']">
                                                    {{ template.is_active ? 'Ativo' : 'Inativo' }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-gray-500 truncate mt-0.5">{{ template.message }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Planos -->
                    <div v-if="activeTab === 'planos'" class="space-y-5">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold flex items-center gap-2">
                                    <Layers class="w-5 h-5 text-cyan-500" />
                                    Planos
                                </h2>
                                <p class="text-sm text-gray-500 mt-1">
                                    Escolha quais planos este tenant oferece e quantos beneficiários cada um comporta.
                                </p>
                            </div>

                            <div v-if="planosAba === 'dados'" class="flex items-center gap-2">
                                <span v-if="planosHasUnsavedChanges" class="text-xs font-medium text-amber-600">
                                    Alterações não salvas
                                </span>

                                <Button v-if="planosHasUnsavedChanges" variant="outline" :disabled="isSavingPlanos"
                                    @click="descartarPlanos">
                                    Descartar
                                </Button>

                                <Button variant="primary"
                                    :disabled="isSavingPlanos || !planosHasUnsavedChanges || hasQuantidadeInvalida"
                                    @click="salvarPlanos">
                                    <Loader2 v-if="isSavingPlanos" class="w-4 h-4 mr-1 animate-spin" />
                                    <Check v-else class="w-4 h-4 mr-1" />
                                    Salvar
                                </Button>
                            </div>
                        </div>

                        <!-- Sub-abas: Dados do plano | Histórico -->
                        <nav class="flex gap-1 border-b border-gray-200" aria-label="Seções de planos">
                            <button v-for="sub in [{ key: 'dados', label: 'Dados do plano' }, { key: 'historico', label: 'Histórico' }]"
                                :key="sub.key" type="button" :aria-current="planosAba === sub.key ? 'page' : undefined"
                                class="-mb-px inline-flex items-center gap-2 whitespace-nowrap border-b-2 px-4 py-2.5 text-sm font-medium transition-colors"
                                :class="planosAba === sub.key
                                    ? 'border-cyan-600 text-cyan-700'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                                @click="planosAba = sub.key">
                                {{ sub.label }}
                                <span v-if="sub.key === 'dados' && planosHasUnsavedChanges" class="h-2 w-2 rounded-full bg-amber-500"
                                    title="Alterações não salvas" />
                                <span v-if="sub.key === 'historico' && planoRegistros.length"
                                    class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ planoRegistros.length }}</span>
                            </button>
                        </nav>

                        <template v-if="planosAba === 'dados'">
                        <div v-if="!planoRows.length" class="text-center py-10 text-gray-500">
                            Nenhum plano configurado no sistema.
                        </div>

                        <div v-else class="space-y-3">
                            <div v-for="row in planoRows" :key="row.cod_plano"
                                class="flex flex-col sm:flex-row sm:items-center gap-4 p-4 rounded-xl border transition-colors"
                                :class="row.selecionado ? 'border-cyan-200 bg-cyan-50/40' : 'border-gray-200 bg-gray-50'">
                                <label class="flex items-center gap-3 flex-1 min-w-0 cursor-pointer">
                                    <input v-model="row.selecionado" type="checkbox"
                                        :disabled="row.selecionado && row.emUso > 0"
                                        :title="row.selecionado && row.emUso > 0 ? 'Plano com associados vinculados não pode ser removido' : ''"
                                        class="w-5 h-5 rounded border-gray-300 text-cyan-600 focus:ring-cyan-500 cursor-pointer" />
                                    <div class="min-w-0">
                                        <p class="font-medium text-gray-900">{{ row.label }}</p>
                                        <p class="text-xs text-gray-500">
                                            {{ row.siprov ? `Código SIPROV: ${row.cod_plano}` : 'Plano próprio do sistema (sem SIPROV)' }}
                                        </p>
                                        <p v-if="row.emUso > 0 || row.selecionado" class="text-xs mt-1"
                                            :class="row.selecionado && row.emUso >= Number(row.quantidade) ? 'text-amber-600 font-medium' : 'text-gray-600'">
                                            {{ row.emUso }} de {{ row.selecionado ? row.quantidade : 0 }} vaga(s) em uso
                                            <template v-if="row.selecionado && row.emUso >= Number(row.quantidade)"> · cota esgotada</template>
                                        </p>
                                    </div>
                                </label>

                                <Button v-if="row.selecionado && row.emUso > 0" type="button" variant="outline" size="sm"
                                    :disabled="planosHasUnsavedChanges || isSavingPlanos"
                                    :title="planosHasUnsavedChanges ? 'Salve ou descarte as alterações antes de zerar' : 'Volta o saldo ao total contratado'"
                                    @click="abrirZerarContagem(row)">
                                    <RotateCcw class="w-4 h-4 mr-1" />
                                    Zerar contagem
                                </Button>

                                <div class="flex items-center gap-2" :class="{ 'opacity-40': !row.selecionado }">
                                    <span class="text-sm text-gray-600">Quantidade</span>
                                    <div class="flex items-center">
                                        <button type="button" class="btn btn-sm btn-ghost px-2" title="Diminuir"
                                            :disabled="!row.selecionado || Number(row.quantidade) <= quantidadeMinima(row)"
                                            @click="row.quantidade = Math.max(quantidadeMinima(row), Number(row.quantidade) - 1)">
                                            <Minus class="w-4 h-4" />
                                        </button>
                                        <input v-model.number="row.quantidade" type="number" :min="quantidadeMinima(row)" step="1"
                                            :disabled="!row.selecionado" :aria-label="`Quantidade do plano ${row.label}`"
                                            class="w-24 px-2 py-1.5 text-center rounded-lg border bg-white text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500"
                                            :class="isQuantidadeInvalida(row) ? 'border-red-300 bg-red-50' : 'border-gray-300'" />
                                        <button type="button" class="btn btn-sm btn-ghost px-2" title="Aumentar"
                                            :disabled="!row.selecionado"
                                            @click="row.quantidade = (Number(row.quantidade) || 0) + 1">
                                            <Plus class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between px-1 pt-1 text-sm text-gray-600">
                                <span>{{ planosSelecionados.length }} de {{ planoRows.length }} plano(s) selecionado(s)</span>
                                <span>Em uso: <strong class="text-gray-900">{{ totalEmUsoPlanos }}</strong> de <strong class="text-gray-900">{{ totalQuantidadePlanos }}</strong></span>
                            </div>
                        </div>

                        </template>

                        <!-- Histórico de registros (auditoria) -->
                        <div v-else class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-500">
                                    Pacientes registrados nos planos: quem registrou, de onde e como ficou o saldo.
                                </p>
                            </div>

                            <!-- Totalizadores por plano (respeitam busca e período) -->
                            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                                <button type="button" :aria-pressed="!historicoFiltros.plano"
                                    class="rounded-xl border p-4 text-left transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500"
                                    :class="!historicoFiltros.plano ? 'border-cyan-300 bg-cyan-50' : 'border-gray-200 bg-white hover:bg-gray-50'"
                                    @click="historicoFiltros.plano = ''">
                                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Total de registros</p>
                                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ planoRegistrosTotais.total }}</p>
                                    <p class="text-xs text-gray-500">todos os planos</p>
                                </button>

                                <button v-for="card in planoRegistrosTotais.planos" :key="card.cod_plano" type="button"
                                    :aria-pressed="historicoFiltros.plano === card.cod_plano"
                                    :title="historicoFiltros.plano === card.cod_plano ? 'Remover filtro deste plano' : 'Filtrar por este plano'"
                                    class="rounded-xl border p-4 text-left transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500"
                                    :class="historicoFiltros.plano === card.cod_plano ? 'border-cyan-300 bg-cyan-50' : 'border-gray-200 bg-white hover:bg-gray-50'"
                                    @click="alternarHistoricoPlano(card.cod_plano)">
                                    <p class="truncate text-xs font-medium uppercase tracking-wide text-gray-500">{{ card.plano }}</p>
                                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ card.total }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ planoRegistrosTotais.total ? Math.round((card.total / planoRegistrosTotais.total) * 100) : 0 }}% do total
                                    </p>
                                </button>
                            </div>

                            <!-- Filtros -->
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-12 lg:items-end">
                                <div class="lg:col-span-4">
                                    <label for="hist_busca" class="mb-1 block text-xs font-medium text-gray-600">Quem registrou ou paciente</label>
                                    <div class="relative">
                                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                        <input id="hist_busca" v-model="historicoFiltros.busca" type="search" placeholder="Buscar por nome..."
                                            class="h-10 w-full rounded-lg border border-gray-300 bg-white pl-9 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500" />
                                    </div>
                                </div>
                                <div class="lg:col-span-2">
                                    <label for="hist_plano" class="mb-1 block text-xs font-medium text-gray-600">Plano</label>
                                    <select id="hist_plano" v-model="historicoFiltros.plano"
                                        class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500">
                                        <option value="">Todos</option>
                                        <option v-for="plano in planos" :key="plano.value" :value="plano.value">{{ plano.label }}</option>
                                    </select>
                                </div>
                                <div class="lg:col-span-2">
                                    <label for="hist_de" class="mb-1 block text-xs font-medium text-gray-600">De</label>
                                    <input id="hist_de" v-model="historicoFiltros.de" type="datetime-local"
                                        class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500" />
                                </div>
                                <div class="lg:col-span-2">
                                    <label for="hist_ate" class="mb-1 block text-xs font-medium text-gray-600">Até</label>
                                    <input id="hist_ate" v-model="historicoFiltros.ate" type="datetime-local" :min="historicoFiltros.de || undefined"
                                        class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500" />
                                </div>
                                <div class="flex items-center gap-2 lg:col-span-2">
                                    <Button v-if="historicoTemFiltro" type="button" variant="outline" class="h-10" @click="limparHistoricoFiltros">
                                        <X class="mr-1 h-4 w-4" />
                                        Limpar
                                    </Button>
                                    <Loader2 v-if="historicoCarregando" class="h-4 w-4 animate-spin text-gray-400" />
                                </div>
                            </div>

                            <p v-if="!planoRegistros.length" class="py-6 text-center text-sm text-gray-500">
                                {{ historicoTemFiltro ? 'Nenhum registro encontrado com esses filtros.' : 'Nenhum registro ainda.' }}
                            </p>

                            <div v-else class="overflow-x-auto rounded-xl border border-gray-200 transition-opacity"
                                :class="{ 'opacity-60': historicoCarregando }">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        <tr>
                                            <th class="px-4 py-3">Data</th>
                                            <th class="px-4 py-3">Paciente</th>
                                            <th class="px-4 py-3">Plano</th>
                                            <th class="px-4 py-3">Origem</th>
                                            <th class="px-4 py-3">Usuário</th>
                                            <th class="px-4 py-3">IP</th>
                                            <th class="px-4 py-3">Dispositivo</th>
                                            <th class="px-4 py-3 text-right">Saldo após</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        <tr v-for="registro in planoRegistros" :key="registro.id">
                                            <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ registro.data }}</td>
                                            <td class="px-4 py-3 font-medium text-gray-900">{{ registro.paciente }}</td>
                                            <td class="whitespace-nowrap px-4 py-3">{{ registro.plano }}</td>
                                            <td class="whitespace-nowrap px-4 py-3">{{ registro.origem }}</td>
                                            <td class="whitespace-nowrap px-4 py-3">{{ registro.usuario || '—' }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-600">{{ registro.ip || '—' }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 text-gray-600" :title="registro.user_agent">
                                                <template v-if="registro.dispositivo">
                                                    {{ registro.dispositivo.tipo }} · {{ registro.dispositivo.sistema }} · {{ registro.dispositivo.navegador }}
                                                </template>
                                                <template v-else>—</template>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                                <span v-if="registro.saldo !== null" :class="registro.saldo < 1 ? 'font-semibold text-amber-700' : 'text-gray-900'">
                                                    {{ registro.saldo }} de {{ registro.quantidade }}
                                                </span>
                                                <span v-else>—</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <p v-if="planoRegistros.length >= planoRegistrosLimite" class="text-xs text-gray-500">
                                Mostrando os {{ planoRegistrosLimite }} registros mais recentes. Use os filtros para encontrar registros mais antigos.
                            </p>
                        </div>
                    </div>

                    <!-- Pacientes -->
                    <div v-if="activeTab === 'patients'" class="space-y-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-base font-semibold flex items-center gap-2 text-gray-800">
                                    <Users class="w-5 h-5 text-cyan-500" />
                                    Pacientes
                                </h2>
                                <p class="text-sm text-gray-500 mt-0.5">
                                    Beneficiário cadastrados neste parceiro.
                                </p>
                            </div>
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold"
                                :class="patients.length ? 'bg-cyan-50 text-cyan-700 border border-cyan-200' : 'bg-gray-50 text-gray-500 border border-gray-200'">
                                <Users class="w-4 h-4" />
                                {{ patients.length }} paciente{{ patients.length !== 1 ? 's' : '' }}
                            </span>
                        </div>

                        <!-- Totalizadores por origem do registro -->
                        <div v-if="patients.length" class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                            <button type="button" :aria-pressed="!registroFiltro"
                                class="rounded-xl border p-4 text-left transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500"
                                :class="!registroFiltro ? 'border-cyan-300 bg-cyan-50' : 'border-gray-200 bg-white hover:bg-gray-50'"
                                @click="registroFiltro = ''; patientPage = 1">
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Total de pacientes</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ patients.length }}</p>
                                <p class="text-xs text-gray-500">todas as origens</p>
                            </button>

                            <button v-for="card in totaisPorRegistro" :key="card.key" type="button"
                                :aria-pressed="registroFiltro === card.key"
                                :title="registroFiltro === card.key ? 'Remover filtro' : 'Filtrar por esta origem'"
                                class="rounded-xl border p-4 text-left transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500"
                                :class="registroFiltro === card.key ? 'border-cyan-300 bg-cyan-50' : 'border-gray-200 bg-white hover:bg-gray-50'"
                                @click="alternarRegistro(card.key)">
                                <p class="flex items-center gap-1.5 truncate text-xs font-medium uppercase tracking-wide text-gray-500">
                                    <span aria-hidden="true">{{ registroIcon(card.key) }}</span>
                                    {{ card.label }}
                                </p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ card.total }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ patients.length ? Math.round((card.total / patients.length) * 100) : 0 }}% do total
                                </p>
                            </button>
                        </div>

                        <div v-if="patients.length">
                            <div class="relative w-full">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                                <input v-model="patientSearch" type="text" placeholder="Buscar paciente..."
                                    class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-gray-200 bg-white text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all"
                                    @input="patientPage = 1" />
                                <button v-if="patientSearch" type="button" @click="patientSearch = ''"
                                    class="absolute inset-y-0 right-2 flex items-center text-gray-400 hover:text-gray-600 transition-colors">
                                    <X class="w-4 h-4" />
                                </button>
                            </div>

                            <div v-if="patientSearch || registroFiltro" class="text-xs text-gray-500 mt-2">
                                {{ filteredPatients.length }} de {{ patients.length }} resultado(s)
                            </div>

                            <div class="border rounded-xl border-gray-200 bg-white shadow-sm overflow-hidden mt-3">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th
                                                    class="px-3 py-3 text-center text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                                    Código</th>
                                                <th
                                                    class="px-3 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                                    Nome</th>
                                                <th
                                                    class="px-3 py-3 text-center text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                                    CPF</th>
                                                <th
                                                    class="px-3 py-3 text-center text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                                    Email</th>
                                                <th
                                                    class="px-3 py-3 text-center text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                                    Sexo</th>
                                                <th
                                                    class="px-3 py-3 text-center text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                                    Nascimento</th>
                                                <th
                                                    class="px-3 py-3 text-center text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                                    Status</th>
                                                <th
                                                    class="px-3 py-3 text-center text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                                    Registro</th>
                                                <th
                                                    class="px-3 py-3 text-center text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                                    Cadastro</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 bg-white">
                                            <tr v-for="patient in paginatedPatients" :key="patient.id"
                                                class="even:bg-gray-50/50 hover:bg-cyan-50/30 transition-colors">
                                                <td class="px-3 py-3 text-center">
                                                    <span
                                                        class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 text-xs font-bold text-gray-600">
                                                        {{ patient.id }}
                                                    </span>
                                                </td>
                                                <td class="px-3 py-3 text-sm">
                                                    <span class="font-semibold text-gray-900">{{ patient.nome || '—'
                                                        }}</span>
                                                </td>
                                                <td class="px-3 py-3 text-center">
                                                    <span class="font-mono text-xs bg-gray-50 px-2 py-1 rounded">{{
                                                        formatCpf(patient.cpf) }}</span>
                                                </td>
                                                <td class="px-3 py-3 text-center text-sm text-gray-600">
                                                    {{ patient.email || '—' }}
                                                </td>
                                                <td class="px-3 py-3 text-center">
                                                    <span v-if="patient.sexo"
                                                        :class="['inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold', patientSexoColor(patient.sexo)]">
                                                        {{ patientSexoIcon(patient.sexo) }}
                                                    </span>
                                                    <span v-else class="text-gray-300">—</span>
                                                </td>
                                                <td
                                                    class="px-3 py-3 text-center text-sm text-gray-600 whitespace-nowrap">
                                                    {{ patient.data_nascimento || '—' }}
                                                </td>
                                                <td class="px-3 py-3 text-center">
                                                    <span v-if="patient.status"
                                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500" />
                                                        Ativo
                                                    </span>
                                                    <span v-else
                                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500" />
                                                        Inativo
                                                    </span>
                                                </td>
                                                <td class="px-3 py-3 text-center">
                                                    <span v-if="patient.status_registro"
                                                        :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium', registroColor(patient.status_registro)]">
                                                        <span class="text-[10px]">{{
                                                            registroIcon(patient.status_registro) }}</span>
                                                        {{ registroLabel(patient.status_registro) }}
                                                    </span>
                                                    <span v-else class="text-gray-300 text-xs">—</span>
                                                </td>
                                                <td
                                                    class="px-3 py-3 text-center text-sm text-gray-500 whitespace-nowrap">
                                                    {{ formatDateShort(patient.created_at) || '—' }}
                                                </td>
                                            </tr>
                                            <tr v-if="!paginatedPatients.length">
                                                <td colspan="9" class="text-center py-12 text-gray-400 text-sm">
                                                    Nenhum paciente encontrado para "{{ patientSearch }}"
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="flex items-center justify-between px-4 py-3 border-t border-gray-100">
                                    <div class="text-sm text-gray-500">
                                        Mostrando {{ showingFrom }} a {{ showingTo }} de {{ filteredPatients.length }}
                                        resultados
                                    </div>
                                    <div v-if="totalPatientPages > 1" class="flex gap-1">
                                        <Button variant="outline" size="sm" class="px-2 py-1 text-sm h-8"
                                            :disabled="patientPage <= 1" @click="goToPatientPage(patientPage - 1)">
                                            <ChevronLeft class="w-4 h-4" />
                                        </Button>
                                        <Button v-for="link in patientPageLinks"
                                            :key="link.page ?? 'dots-' + link.label"
                                            :variant="link.active ? 'primary' : 'outline'"
                                            :disabled="link.page === null" size="sm" class="px-3 py-1 text-sm h-8"
                                            @click="link.page && goToPatientPage(link.page)">
                                            {{ link.label }}
                                        </Button>
                                        <Button variant="outline" size="sm" class="px-2 py-1 text-sm h-8"
                                            :disabled="patientPage >= totalPatientPages"
                                            @click="goToPatientPage(patientPage + 1)">
                                            <ChevronRight class="w-4 h-4" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-16 text-gray-500">
                            <div
                                class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                <Users class="w-8 h-8 text-gray-400" />
                            </div>
                            <p class="text-lg font-medium text-gray-900">Nenhum paciente</p>
                            <p class="text-sm text-gray-500 mt-1">Nenhum paciente cadastrado neste parceiro.</p>
                        </div>
                    </div>

                    <!-- Logo -->
                    <EbaLogo v-if="activeTab === 'logo'" :tenant-id="tenant.id" :upload-url="route('pagina.configuracao.logo.store', tenant.id)" :delete-url="route('pagina.configuracao.logo.destroy', tenant.id)" v-model:list="arquivosLocal" />

                    <!-- Cartão Dinâmico -->
                    <div v-if="activeTab === 'cartao-dinamico'" class="space-y-5">
                        <div>
                            <h2 class="text-lg font-semibold flex items-center gap-2">
                                <Sparkles class="w-5 h-5 text-purple-500" />
                                Cartão Dinâmico
                            </h2>
                            <p class="text-sm text-gray-500 mt-1">
                                Configure a identidade visual do cartão de paciente deste parceiro: cores, logo e
                                imagens de frente/verso. Este cartão é gerado à parte do "Gerar Cartão" tradicional.
                            </p>
                        </div>

                        <!-- Opções de configuração -->
                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900">Opções de configuração</h3>
                                    <p class="text-sm text-gray-500 mt-1">
                                        Busque e edite as configurações disponíveis para este tenant.
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 px-4 py-2 bg-purple-50 text-purple-700 rounded-lg text-sm shrink-0">
                                    <span class="font-medium">
                                        {{ cartaoDinamicoConfigurations.length }} configurações
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Filtros -->
                        <div class="space-y-4">
                            <!-- Busca em primeiro lugar: é o caminho mais rápido até a configuração desejada -->
                            <div class="max-w-lg">
                                <SearchInput v-model="cartaoDinamicoSearch" placeholder="Buscar configurações..."
                                    focus-color="purple" size="lg" />
                            </div>

                            <div class="flex items-center gap-2 overflow-x-auto pb-1 cartao-dinamico-scrollbar-hide">
                                <button v-for="category in cartaoDinamicoAllCategories" :key="category" type="button"
                                    @click="cartaoDinamicoActiveCategory = category"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-colors"
                                    :class="cartaoDinamicoActiveCategory === category
                                        ? 'bg-purple-600 text-white shadow-sm'
                                        : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200'">
                                    <component :is="cartaoDinamicoCategoryIcon(category)" class="w-3.5 h-3.5 shrink-0" />
                                    {{ category === 'all' ? 'Todas' : category }}
                                </button>
                            </div>
                        </div>

                        <!-- Conteúdo -->
                        <div v-if="Object.keys(cartaoDinamicoConfigsByCategory).length" class="space-y-8">
                            <section v-for="(configs, category) in cartaoDinamicoConfigsByCategory" :key="category"
                                class="space-y-4">
                                <div class="flex items-center gap-2">
                                    <component :is="cartaoDinamicoCategoryIcon(category)"
                                        class="w-4 h-4 text-gray-400 shrink-0" />
                                    <h4 class="text-sm font-semibold text-gray-700">{{ category }}</h4>
                                    <div class="flex-1 h-px bg-gray-200"></div>
                                    <span class="text-xs text-gray-400 font-medium">{{ configs.length }} itens</span>
                                </div>

                                <!-- No máximo 2 colunas: menos alvos visuais competindo por atenção ao mesmo tempo -->
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                    <div v-for="config in configs" :key="config.key"
                                        class="bg-white rounded-xl border border-gray-200 overflow-hidden transition-shadow"
                                        :class="{ 'shadow-md': isCartaoDinamicoExpanded(config.key) }">
                                        <!-- Header -->
                                        <div class="p-5 cursor-pointer select-none"
                                            @click="toggleCartaoDinamicoExpand(config.key)" role="button"
                                            :aria-expanded="isCartaoDinamicoExpanded(config.key)">
                                            <div class="flex items-start gap-3">
                                                <!-- Ícone fixo por tipo: reconhecimento visual sem precisar ler o texto -->
                                                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                                                    :class="cartaoDinamicoTypeIconClasses(config)">
                                                    <component :is="cartaoDinamicoTypeIcon(config)" class="w-5 h-5" />
                                                </div>

                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <h5 class="font-semibold text-gray-900 text-sm"
                                                            v-html="cartaoDinamicoHighlight(config.label)"></h5>
                                                        <span v-if="cartaoDinamicoHasUnsavedChanges(config)"
                                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[11px] font-medium text-amber-700 bg-amber-50 border border-amber-200 shrink-0">
                                                            <AlertCircle class="w-3 h-3" />
                                                            não salvo
                                                        </span>
                                                    </div>
                                                    <p class="text-xs text-gray-500 mt-1"
                                                        v-html="cartaoDinamicoHighlight(config.description)"></p>
                                                </div>
                                                <div class="flex items-center gap-2 shrink-0">
                                                    <img v-if="config.type === 'image' && cartaoDinamicoAssets[config.key]"
                                                        :src="cartaoDinamicoAssets[config.key]"
                                                        class="w-8 h-8 rounded-lg object-cover border border-gray-200"
                                                        alt="Preview" />
                                                    <span v-if="config.type === 'toggle'" :class="[
                                                        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium',
                                                        props.cartaoDinamicoEnabled
                                                            ? 'bg-purple-100 text-purple-700 border border-purple-200'
                                                            : 'bg-gray-100 text-gray-500 border border-gray-200',
                                                    ]">
                                                        {{ props.cartaoDinamicoEnabled ? 'Ativado' : 'Desativado' }}
                                                    </span>
                                                    <span v-if="config.type === 'qrcode'" :class="[
                                                        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium',
                                                        qrcodeHabilitadoCartaoDinamico
                                                            ? 'bg-indigo-100 text-indigo-700 border border-indigo-200'
                                                            : 'bg-gray-100 text-gray-500 border border-gray-200',
                                                    ]">
                                                        {{ qrcodeHabilitadoCartaoDinamico ? 'Ativado' : 'Desativado' }}
                                                    </span>
                                                    <svg class="w-4 h-4 text-gray-400 transition-transform duration-200"
                                                        :class="{ 'rotate-180': isCartaoDinamicoExpanded(config.key) }"
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Body -->
                                        <Transition name="cartao-dinamico-expand">
                                            <div v-if="isCartaoDinamicoExpanded(config.key)"
                                                class="border-t border-gray-100 p-5 space-y-4">
                                                <!-- Estilo -->
                                                <template v-if="config.type === 'style'">
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                        <div class="grid gap-2">
                                                            <Label for="cartao_cor_primaria">Cor Primária</Label>
                                                            <div class="flex items-center gap-3">
                                                                <input id="cartao_cor_primaria" type="color"
                                                                    v-model="corPrimariaCartaoDinamico"
                                                                    class="w-12 h-10 rounded border border-input cursor-pointer" />
                                                                <input type="text" v-model="corPrimariaCartaoDinamico"
                                                                    placeholder="#22d3ee"
                                                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm flex-1" />
                                                            </div>
                                                        </div>
                                                        <div class="grid gap-2">
                                                            <Label for="cartao_cor_secundaria">Cor Secundária</Label>
                                                            <div class="flex items-center gap-3">
                                                                <input id="cartao_cor_secundaria" type="color"
                                                                    v-model="corSecundariaCartaoDinamico"
                                                                    class="w-12 h-10 rounded border border-input cursor-pointer" />
                                                                <input type="text" v-model="corSecundariaCartaoDinamico"
                                                                    placeholder="#0e7490"
                                                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm flex-1" />
                                                            </div>
                                                        </div>
                                                        <div class="grid gap-2">
                                                            <Label for="cartao_cor_texto">Cor do Texto</Label>
                                                            <div class="flex items-center gap-3">
                                                                <input id="cartao_cor_texto" type="color"
                                                                    v-model="corTextoCartaoDinamico"
                                                                    class="w-12 h-10 rounded border border-input cursor-pointer" />
                                                                <input type="text" v-model="corTextoCartaoDinamico"
                                                                    placeholder="#ffffff"
                                                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm flex-1" />
                                                            </div>
                                                        </div>
                                                        <div class="grid gap-2">
                                                            <Label for="cartao_fonte">Fonte</Label>
                                                            <select id="cartao_fonte" v-model="fonteCartaoDinamico"
                                                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                                                <option v-for="fonte in fontesCartaoDinamico"
                                                                    :key="fonte.value" :value="fonte.value">
                                                                    {{ fonte.label }}
                                                                </option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="flex justify-end pt-2 border-t border-gray-100">
                                                        <Button type="button" :disabled="isSavingCoresCartaoDinamico"
                                                            @click="salvarCoresCartaoDinamico">
                                                            {{ isSavingCoresCartaoDinamico ? 'Salvando...' : 'Salvar Estilo' }}
                                                        </Button>
                                                    </div>
                                                </template>

                                                <!-- Imagens -->
                                                <template v-else-if="config.type === 'image'">
                                                    <ImageUpload :label="config.label" :description="config.description"
                                                        :model-value="cartaoDinamicoAssets[config.key]"
                                                        :preview-url="cartaoDinamicoAssets[config.key]"
                                                        :show-posicao-selector="false"
                                                        @update:model-value="(val) => handleCartaoImagemChange(config.key, val)" />
                                                </template>

                                                <!-- Status -->
                                                <template v-else-if="config.type === 'toggle'">
                                                    <div
                                                        class="flex items-start gap-3 p-4 rounded-xl border border-purple-200 bg-purple-50 text-purple-800">
                                                        <Info class="w-5 h-5 shrink-0 mt-0.5" />
                                                        <div class="text-sm">
                                                            <p class="font-medium">
                                                                {{ config.label }}
                                                            </p>
                                                            <p class="text-xs text-purple-700 mt-1">
                                                                {{ config.description }}
                                                            </p>
                                                        </div>
                                                    </div>

                                                    <div class="flex items-center justify-between gap-4 mt-4">
                                                        <p class="text-sm text-gray-600 flex-1">
                                                            Status atual do cartão dinâmico.
                                                        </p>
                                                        <button @click="toggleCartaoDinamico"
                                                            :disabled="isTogglingCartaoDinamico" :class="[
                                                                'relative inline-flex h-7 w-12 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2',
                                                                props.cartaoDinamicoEnabled ? 'bg-purple-600' : 'bg-gray-200',
                                                                isTogglingCartaoDinamico ? 'opacity-50 cursor-not-allowed' : '',
                                                            ]">
                                                            <span :class="[
                                                                'pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out',
                                                                props.cartaoDinamicoEnabled ? 'translate-x-5' : 'translate-x-0',
                                                            ]" />
                                                        </button>
                                                    </div>
                                                </template>

                                                <!-- QR Code e Textos do Verso -->
                                                <template v-else-if="config.type === 'qrcode'">
                                                    <div
                                                        class="flex items-start gap-3 p-4 rounded-xl border border-cyan-200 bg-cyan-50 text-cyan-800">
                                                        <Info class="w-5 h-5 shrink-0 mt-0.5" />
                                                        <div class="text-sm">
                                                            <p class="font-medium">
                                                                QR Code e textos do verso do cartão
                                                            </p>
                                                            <p class="text-xs text-cyan-700 mt-1">
                                                                O QR Code é exibido no centro do verso do cartão
                                                                dinâmico. Os textos abaixo substituem os textos fixos
                                                                ao lado do QR Code e no rodapé do verso.
                                                            </p>
                                                        </div>
                                                    </div>

                                                    <div class="flex items-center justify-between gap-4 p-4 rounded-xl border border-gray-200 bg-gray-50">
                                                        <div class="text-sm">
                                                            <p class="font-medium text-gray-800">QR Code habilitado</p>
                                                            <p class="text-xs text-gray-500 mt-0.5">
                                                                Quando desativado, o verso do cartão exibe "QR Code desativado" no lugar da imagem.
                                                            </p>
                                                        </div>
                                                        <button type="button"
                                                            @click="qrcodeHabilitadoCartaoDinamico = !qrcodeHabilitadoCartaoDinamico"
                                                            :class="[
                                                                'relative inline-flex h-7 w-12 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out',
                                                                qrcodeHabilitadoCartaoDinamico ? 'bg-indigo-600' : 'bg-gray-200',
                                                            ]">
                                                            <span :class="[
                                                                'pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out',
                                                                qrcodeHabilitadoCartaoDinamico ? 'translate-x-5' : 'translate-x-0',
                                                            ]" />
                                                        </button>
                                                    </div>

                                                    <div class="grid gap-2">
                                                        <Label for="cartao_qrcode_dados">O que colocar no QR Code</Label>
                                                        <textarea id="cartao_qrcode_dados" v-model="qrcodeDadosCartaoDinamico"
                                                            rows="2" placeholder="URL, texto ou dado que será codificado no QR Code"
                                                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm"></textarea>
                                                        <p class="text-xs text-gray-400">
                                                            Se deixado em branco, é usado o link padrão configurado no sistema.
                                                        </p>
                                                    </div>

                                                    <div class="grid gap-2">
                                                        <Label for="cartao_verso_texto_info">Texto ao lado do QR Code</Label>
                                                        <input id="cartao_verso_texto_info" type="text"
                                                            v-model="versoTextoInfoCartaoDinamico" placeholder="Solicite atendimento 24h"
                                                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" />
                                                    </div>

                                                    <div class="grid gap-2">
                                                        <Label for="cartao_verso_rodape">Texto de rodapé do verso</Label>
                                                        <textarea id="cartao_verso_rodape" v-model="versoRodapeCartaoDinamico"
                                                            rows="4" placeholder="Uma linha por frase"
                                                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm"></textarea>
                                                        <p class="text-xs text-gray-400">
                                                            Cada linha vira uma linha separada no cartão.
                                                        </p>
                                                    </div>

                                                    <div class="flex justify-end pt-2 border-t border-gray-100">
                                                        <Button type="button" :disabled="isSavingQrcodeCartaoDinamico"
                                                            @click="salvarQrcodeCartaoDinamico">
                                                            {{ isSavingQrcodeCartaoDinamico ? 'Salvando...' : 'Salvar' }}
                                                        </Button>
                                                    </div>
                                                </template>
                                            </div>
                                        </Transition>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <!-- Empty -->
                        <div v-else class="text-center py-16 bg-white rounded-xl border border-gray-200">
                            <h3 class="text-sm font-semibold text-gray-900 mb-1">Nenhuma configuração encontrada</h3>
                            <p class="text-sm text-gray-500">Tente ajustar os filtros ou termos de busca.</p>
                            <button v-if="cartaoDinamicoSearch || cartaoDinamicoActiveCategory !== 'all'" type="button"
                                @click="clearCartaoDinamicoFiltros"
                                class="mt-4 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                                Limpar filtros
                            </button>
                        </div>
                    </div>

                    <!-- Telemedicina -->
                    <div v-if="activeTab === 'telemedicina'"
                        class="rounded-2xl border border-gray-100 bg-white p-5 space-y-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-base font-semibold flex items-center gap-2 text-gray-800">
                                    <HeartPulse class="w-5 h-5 text-red-500" />
                                    Telemedicina
                                </h2>
                                <p class="text-sm text-gray-600 mt-0.5">Gerencie os vínculos de telemedicina deste
                                    parceiro. Ao
                                    vincular um associado SIPROV, o registro também será vinculado à página e um
                                    paciente será
                                    criado automaticamente caso ainda não exista.</p>
                            </div>
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold"
                                :class="telemedicinaVinculados.length ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-gray-50 text-gray-500 border border-gray-200'">
                                <CheckCircle v-if="telemedicinaVinculados.length" class="w-4 h-4" />
                                <Users v-else class="w-4 h-4" />
                                {{ telemedicinaVinculados.length }} associado{{ telemedicinaVinculados.length !== 1 ?
                                    's' : '' }}
                            </span>
                        </div>

                        <!-- Buscar / Filtrar -->
                        <div class="space-y-3">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                                <input v-model="telemedicinaSearch" type="text"
                                    placeholder="Filtrar vinculados por nome, CPF ou plano..."
                                    class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-gray-200 bg-gray-50 text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all" />
                                <button v-if="telemedicinaSearch" type="button" @click="telemedicinaSearch = ''"
                                    class="absolute inset-y-0 right-2 flex items-center text-gray-400 hover:text-gray-600 transition-colors">
                                    <X class="w-4 h-4" />
                                </button>
                            </div>

                            <div v-if="telemedicinaSearch && telemedicinaVinculados.length" class="text-xs text-gray-500">
                                {{ filteredTelemedicinaVinculados.length }} de {{ telemedicinaVinculados.length }}
                                resultado(s)
                            </div>
                        </div>

                        <!-- Itens vinculados -->
                        <div v-if="filteredTelemedicinaVinculados.length" class="space-y-2">
                            <div v-for="item in filteredTelemedicinaVinculados" :key="item.id"
                                class="flex items-center gap-4 p-4 rounded-xl border border-gray-100 bg-gray-50/50 hover:bg-gray-50 hover:border-green-200 transition-colors group">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-900">{{ item.data?.title || '-' }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5 flex items-center gap-2 flex-wrap">
                                        <template v-if="item.data?.cpf_cnpj">
                                            <span class="inline-flex items-center gap-1">
                                                <span class="w-1 h-1 rounded-full bg-gray-400" />
                                                CPF: {{ item.data.cpf_cnpj }}
                                            </span>
                                        </template>
                                        <template v-if="item.data?.plano_label">
                                            <span class="inline-flex items-center gap-1">
                                                <span class="w-1 h-1 rounded-full bg-gray-400" />
                                                {{ item.data.plano_label }}
                                            </span>
                                        </template>
                                        <template v-if="item.data?.options?.length">
                                            <span v-for="option in item.data.options" :key="option.value"
                                                class="inline-flex items-center gap-1">
                                                <span class="w-1 h-1 rounded-full bg-gray-400" />
                                                {{ option.label }}
                                            </span>
                                        </template>
                                    </p>
                                </div>
                                <button @click="unlinkTelemedicina(item)"
                                    class="shrink-0 flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-all opacity-0 group-hover:opacity-100 focus:opacity-100"
                                    title="Desvincular">
                                    <Trash2 class="w-3.5 h-3.5" />
                                    Desvincular
                                </button>
                            </div>
                        </div>

                        <div v-else class="text-center py-10">
                            <div class="w-14 h-14 mx-auto rounded-full flex items-center justify-center"
                                :class="telemedicinaVinculados.length ? 'bg-amber-50' : 'bg-gray-50'">
                                <Search v-if="telemedicinaVinculados.length" class="w-6 h-6 text-amber-400" />
                                <HeartPulse v-else class="w-6 h-6 text-gray-300" />
                            </div>
                            <p class="text-sm font-medium text-gray-700 mt-3" v-if="telemedicinaVinculados.length">
                                Nenhum resultado
                                para "{{ telemedicinaSearch }}"</p>
                            <p class="text-sm font-medium text-gray-700 mt-3" v-else>Nenhum associado vinculado</p>
                            <p class="text-xs text-gray-400 mt-1" v-if="telemedicinaVinculados.length">Tente outro termo
                                de busca.
                            </p>
                            <p class="text-xs text-gray-400 mt-1" v-else>Adicione associados da SIPROV para habilitar a
                                telemedicina.</p>
                        </div>

                        <!-- Ação principal: Adicionar -->
                        <div class="pt-1">
                            <Button variant="primary" class="w-full" @click="openSiprovModal">
                                <Plus class="w-4 h-4 mr-2" />
                                Adicionar associado SIPROV
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <FormSelectorDialog v-model:open="dialogOpen" v-model="selectedFormIds" :forms="availableForms"
            @confirm="syncForms" />

        <ConfirmDeleteModal :show="zerarModal.show" title="Zerar contagem de registrados"
            :message="zerarModal.row ? `Zerar a contagem do plano ${zerarModal.row.label}? ${zerarModal.row.emUso} vaga(s) em uso voltam a ficar disponíveis.` : 'Zerar a contagem deste plano?'"
            warning-message="Os associados já vinculados e o histórico de registros são mantidos. A ação fica registrada na auditoria."
            confirm-text="Sim, zerar" cancel-text="Cancelar" :isProcessing="zerarModal.isProcessing"
            @close="fecharZerarContagem" @confirm="confirmarZerarContagem" />

        <ConfirmDeleteModal :show="confirmDialogOpen" title="Remover vínculo" message="Deseja remover esse vínculo?"
            confirm-text="Sim, remover" cancel-text="Cancelar" :isProcessing="isRemoving" @close="closeRemoveLinkDialog"
            @confirm="confirmRemoveLink" />

        <ConfirmDeleteModal :show="telemedicinaUnlinkModal" title="Desvincular associado"
            :message="telemedicinaUnlinkItem ? 'Deseja desvincular ' + telemedicinaUnlinkItem.data?.title + '?' : 'Deseja desvincular este item?'"
            warning-message="O paciente vinculado por este associado também será removido. Esta ação não pode ser desfeita."
            confirm-text="Sim, desvincular" cancel-text="Cancelar" :isProcessing="isUnlinkingTelemedicina"
            @close="closeUnlinkTelemedicina" @confirm="confirmUnlinkTelemedicina" />

        <ConfirmDeleteModal :show="smsDeleteModal" title="Remover template SMS"
            :message="smsDeleteItem ? `Deseja remover o template '${smsDeleteItem.name}'?` : 'Deseja remover este template?'"
            confirm-text="Sim, remover" cancel-text="Cancelar" :isProcessing="isDeletingSms"
            @close="closeSmsDeleteModal" @confirm="confirmDeleteSmsTemplate" />

        <SmsTemplateModal :show="smsModalOpen" :template="smsModalTemplate" event="patient.created"
            :tenants="allTenants || []" @close="closeSmsModal" />

        <!-- SIPROV Modal -->
        <div v-if="siprovModalOpen"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 backdrop-blur-sm">
            <div
                class="mx-4 w-full max-w-6xl max-h-[80vh] rounded-2xl bg-white shadow-2xl border border-gray-100 flex flex-col">
                <div class="flex items-center justify-between p-6 border-b border-gray-100">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Vincular SIPROV</h3>
                        <p class="text-sm text-gray-500 mt-0.5">Busque e selecione registros do SIPROV para vincular a
                            este
                            parceiro.</p>
                        <div
                            class="mt-3 p-3 bg-cyan-50 border border-cyan-200 rounded-lg text-xs text-cyan-800 space-y-1.5">
                            <div class="flex items-start gap-2">
                                <Info class="w-4 h-4 shrink-0 mt-0.5" />
                                <span>Ao criar o vínculo, um <strong>paciente</strong> será gerado automaticamente com o
                                    CPF do
                                    associado e o status de registro <strong>Vínculo</strong>, caso ainda não
                                    exista.</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <Users class="w-4 h-4 shrink-0 mt-0.5" />
                                <span>O paciente ficará disponível na aba <strong>Pacientes</strong> vinculado a este
                                    parceiro.</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <Layers class="w-4 h-4 shrink-0 mt-0.5" />
                                <span v-if="planoVagas.length">
                                    Vagas por plano:
                                    <template v-for="(vaga, i) in planoVagas" :key="vaga.cod_plano">
                                        <strong>{{ vaga.label }}</strong> {{ vaga.disponivel }} de {{ vaga.quantidade }}<template v-if="i < planoVagas.length - 1"> · </template>
                                    </template>
                                </span>
                                <span v-else>
                                    Nenhum plano habilitado. Configure os planos na aba <strong>Planos</strong> antes de
                                    vincular associados.
                                </span>
                            </div>
                        </div>
                    </div>
                    <button @click="siprovModalOpen = false"
                        class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-6 border-b border-gray-100">
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <input v-model="siprovSearch" type="text"
                            placeholder="Buscar por nome, CPF, e-mail ou código de integração..."
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all"
                            @keyup.enter="searchSiprov(1)" />
                        <Button variant="primary" size="sm" class="absolute right-1.5 top-1/2 -translate-y-1/2"
                            :disabled="isSearchingSiprov" @click="searchSiprov(1)">
                            <Loader2 v-if="isSearchingSiprov" class="w-4 h-4 animate-spin" />
                            <Search v-else class="w-4 h-4" />
                        </Button>
                    </div>
                </div>

                <div v-if="siprovError"
                    class="mx-6 mt-3 p-4 rounded-lg text-sm font-medium bg-amber-100 text-amber-800 border border-amber-200">
                    {{ siprovError }}
                </div>

                <!-- Step indicator -->
                <div class="flex items-center gap-2 px-6 pt-2">
                    <span class="flex items-center gap-1.5 text-xs font-medium"
                        :class="siprovSelected.length > 0 ? 'text-green-600' : 'text-cyan-600'">
                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold"
                            :class="siprovSelected.length > 0 ? 'bg-green-100 text-green-700' : 'bg-cyan-100 text-cyan-700'">1</span>
                        Buscar
                    </span>
                    <span class="text-gray-300">→</span>
                    <span class="flex items-center gap-1.5 text-xs font-medium"
                        :class="siprovSelected.length > 0 ? 'text-cyan-600' : 'text-gray-400'">
                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold"
                            :class="siprovSelected.length > 0 ? 'bg-cyan-100 text-cyan-700' : 'bg-gray-100 text-gray-500'">2</span>
                        Selecionar
                    </span>
                    <span class="text-gray-300">→</span>
                    <span class="flex items-center gap-1.5 text-xs font-medium text-gray-400">
                        <span
                            class="w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold bg-gray-100 text-gray-500">3</span>
                        Confirmar
                    </span>
                </div>

                <div class="flex-1 overflow-y-auto">
                    <div v-if="isSearchingSiprov" class="flex items-center justify-center py-16">
                        <Loader2 class="w-6 h-6 animate-spin text-cyan-500" />
                        <span class="ml-2 text-sm text-gray-500">Buscando associados...</span>
                    </div>

                    <template v-else-if="siprovResults.length">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sticky top-0 z-10">
                                <tr>
                                    <th class="w-10 px-4 py-3 text-left">
                                        <input type="checkbox" :checked="siprovSelected.length === siprovResults.length"
                                            @change="toggleSelectAllSiprov"
                                            class="w-4 h-4 text-cyan-600 bg-gray-100 border-gray-300 rounded focus:ring-cyan-500" />
                                    </th>
                                    <th
                                        class="px-3 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                        #</th>
                                    <th
                                        class="px-3 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                        Associado</th>
                                    <th
                                        class="px-3 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                        CPF/CNPJ</th>
                                    <th
                                        class="px-3 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                        Plano</th>
                                    <th
                                        class="px-3 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                        Benefício</th>
                                    <th
                                        class="px-3 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                        Cadastro</th>
                                    <th
                                        class="px-3 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                        Situação</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-for="item in siprovResults" :key="getSiprovKey(item)"
                                    class="hover:bg-gray-50 transition-colors cursor-pointer"
                                    :class="{ 'bg-cyan-50 hover:bg-cyan-100': siprovSelected.includes(getSiprovKey(item)) }"
                                    @click="toggleSiprovItem(item)">
                                    <td class="px-4 py-3" @click.stop>
                                        <input type="checkbox" :checked="siprovSelected.includes(getSiprovKey(item))"
                                            @change="toggleSiprovItem(item)"
                                            class="w-4 h-4 text-cyan-600 bg-gray-100 border-gray-300 rounded focus:ring-cyan-500" />
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-sm text-gray-900 font-mono">
                                        #{{ item.codPessoa }}
                                    </td>
                                    <td class="px-3 py-3 text-sm">
                                        <div
                                            class="font-medium text-gray-900 group-hover:text-cyan-600 transition-colors">
                                            {{ item.nomePessoa }}
                                        </div>
                                        <div class="text-xs text-gray-500 truncate max-w-xs mt-0.5">
                                            {{ item.email || 'Sem e-mail' }}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-sm text-gray-700">
                                        {{ item.cpfCnpj || '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-sm">
                                        <span v-for="(plano, pIdx) in item.planos" :key="pIdx"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100 mr-1 mb-1">
                                            <FileText class="w-3 h-3" />
                                            {{ plano.nome }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-sm text-gray-600">
                                        #{{ item.codBeneficio }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-sm text-gray-500">
                                        <div>{{ item.dataCadastro || '-' }}</div>
                                        <div v-if="item.dataAdesao" class="text-xs text-gray-400">Adesão: {{
                                            item.dataAdesao }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-sm">
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 border border-green-200">
                                            <CheckCircle class="w-3.5 h-3.5" />
                                            {{ item.situacao }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div v-if="siprovResults.length && (siprovPage > 1 || siprovHasNext)"
                            class="flex items-center justify-between px-6 py-3 border-t border-gray-100 bg-white">
                            <div class="text-sm text-gray-500">
                                Página {{ siprovPage }} · {{ siprovTotal }} resultado(s)
                            </div>
                            <div class="flex items-center gap-2">
                                <Button variant="outline" size="sm" :disabled="siprovPage <= 1 || isSearchingSiprov"
                                    @click="goToSiprovPage(siprovPage - 1)">
                                    Anterior
                                </Button>
                                <Button variant="outline" size="sm" :disabled="!siprovHasNext || isSearchingSiprov"
                                    @click="goToSiprovPage(siprovPage + 1)">
                                    Próxima
                                </Button>
                            </div>
                        </div>
                    </template>

                    <div v-else class="text-center py-16 text-gray-500">
                        <Search class="w-10 h-10 mx-auto text-gray-300 mb-3" />
                        <p class="text-sm" v-if="siprovSearch">Nenhum resultado encontrado.</p>
                        <p class="text-sm" v-else>Digite algo para buscar registros SIPROV.</p>
                    </div>
                </div>

                <div v-if="siprovCotaErros.length"
                    class="px-4 py-3 border-t border-red-200 bg-red-50 text-xs text-red-700 space-y-1">
                    <div v-for="erro in siprovCotaErros" :key="erro" class="flex items-start gap-2">
                        <AlertCircle class="w-4 h-4 shrink-0" />
                        <span>{{ erro }}</span>
                    </div>
                </div>

                <div class="flex items-center justify-between p-4 border-t border-gray-100 bg-gray-50/50"
                    :class="{ 'bg-cyan-50/70 border-cyan-200': siprovSelected.length > 0 }">
                    <span class="text-sm font-medium"
                        :class="siprovSelected.length > 0 ? 'text-cyan-800' : 'text-gray-500'">
                        <template v-if="siprovSelected.length > 0">
                            <CheckCircle class="w-4 h-4 inline-block mr-1.5" />
                            {{ siprovSelected.length }} associado{{ siprovSelected.length !== 1 ? 's' : '' }}
                            selecionado{{ siprovSelected.length !== 1 ? 's' : '' }}
                        </template>
                        <template v-else>
                            {{ siprovResults.length }} resultado(s)
                        </template>
                    </span>
                    <div class="flex items-center gap-2">
                        <Button variant="secondary" @click="siprovModalOpen = false">
                            Cancelar
                        </Button>
                        <Button variant="primary" :disabled="!siprovSelected.length || isSavingSiprov || siprovCotaErros.length > 0"
                            @click="vincularSiprov">
                            <Loader2 v-if="isSavingSiprov" class="w-4 h-4 mr-2 animate-spin" />
                            <Plus v-else class="w-4 h-4 mr-2" />
                            Vincular {{ siprovSelected.length || '' }}
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </CentralAdminLayout>
</template>

<style scoped>
.cartao-dinamico-expand-enter-active,
.cartao-dinamico-expand-leave-active {
    transition: all 0.25s ease;
    overflow: hidden;
}

.cartao-dinamico-expand-enter-from,
.cartao-dinamico-expand-leave-to {
    opacity: 0;
    max-height: 0;
}

.cartao-dinamico-scrollbar-hide::-webkit-scrollbar {
    display: none;
}

.cartao-dinamico-scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
