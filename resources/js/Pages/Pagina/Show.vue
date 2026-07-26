<script setup>
import CentralAdminLayout from '@/Layouts/CentralAdminLayout.vue'
import { router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import ActionDropdown from '@/Components/ActionDropdown.vue'
import PomponeteLink from '@/Components/PomponeteLink.vue'
import FormSelectorDialog from '@/Components/FormSelectorDialog.vue'
import UserBadge from '@/Components/UserBadge.vue'
import Breadcrumb from '@/Components/Breadcrumb.vue'
import { Button } from '@/Components/ui/button'
import { Label } from '@/Components/ui/label'
import FormLinkedCard from '@/Components/Cards/FormLinkedCard.vue'
import { showToast } from '@/Utils/toast'
import ConfirmDeleteModal from '@/Components/ConfirmDeleteModal.vue'
import SmsTemplateModal from '@/Components/SmsTemplateModal.vue'

import {
    Home,
    Building2,
    Globe,
    User,
    Calendar,
    Database,
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
    Users,
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
})

const activeTab = ref('overview')

const isGeneratingDetail = ref(false)
const dialogOpen = ref(false)
const selectedFormIds = ref([])
const isSavingForms = ref(false)

const confirmDialogOpen = ref(false)
const selectedFormToRemove = ref(null)
const isRemoving = ref(false)

const telemedicinaUnlinkModal = ref(false)
const telemedicinaUnlinkItem = ref(null)
const isUnlinkingTelemedicina = ref(false)

const smsModalOpen = ref(false)
const smsModalTemplate = ref(null)

const smsDeleteModal = ref(false)
const smsDeleteItem = ref(null)
const isDeletingSms = ref(false)
const isTogglingStatus = ref(false)

const isSavingTelemedicina = ref(false)
const telemedicinaSearch = ref('')
const selectedTelemedicinaIds = ref([])

const filteredTelemedicinaVinculados = computed(() => {
    const query = siprovSearch.value.toLowerCase().trim()
    if (!query) return props.telemedicinaVinculados
    return props.telemedicinaVinculados.filter(item => {
        const data = item.data || {}
        return (data.title || '').toLowerCase().includes(query)
            || (data.cpf_cnpj || '').toLowerCase().includes(query)
            || (data.plano_label || '').toLowerCase().includes(query)
            || (data.codigo_integracao || '').toLowerCase().includes(query)
    })
})

const linkedTelemedicinaIds = computed(() =>
    props.telemedicinaVinculados.map(v => v.data?.question_id).filter(Boolean)
)

const siprovModalOpen = ref(false)
const siprovSearch = ref('')
const siprovResults = ref([])
const siprovSelected = ref([])
const siprovError = ref(null)
const siprovPage = ref(1)
const siprovHasNext = ref(false)
const siprovTotal = ref(0)
const isSearchingSiprov = ref(false)
const isSavingSiprov = ref(false)

const detail = computed(() => props.tenant?.details?.[0] ?? null)
const user = computed(() => detail.value?.user ?? null)
const availableForms = computed(() => props.forms ?? [])
const domains = computed(() => props.tenant?.domains ?? [])

const tenantName = computed(() => {
    return detail.value?.descricao || detail.value?.slug || props.tenant?.id || 'Tenant'
})

const tenantSlug = computed(() => {
    return detail.value?.slug || props.tenant?.id || '-'
})

const tabs = computed(() => [
    {
        key: 'overview',
        label: 'Visão geral',
        icon: Building2,
    },
    {
        key: 'details',
        label: 'Dados complementares',
        icon: Database,
        disabled: !detail.value,
    },
    {
        key: 'forms',
        label: 'Formulários',
        icon: FileText,
        badge: props.fomrs_tenants.length,
    },
    {
        key: 'responsible',
        label: 'Responsável',
        icon: User,
        disabled: !user.value,
    },
    {
        key: 'domains',
        label: 'Domínios',
        icon: Globe,
        badge: domains.value.length,
    },
    {
        key: 'sms',
        label: 'Templates SMS',
        icon: MessageSquare,
        badge: props.smsTemplates.length,
    },
    {
        key: 'telemedicina',
        label: 'Telemedicina',
        icon: HeartPulse,
        badge: props.telemedicinaVinculados.length || null,
    },
    {
        key: 'config',
        label: 'Configuração',
        icon: Settings,
    },
])

const breadcrumbs = computed(() => [
    {
        label: 'Página de Parceiros',
        href: route('pagina.index'),
        icon: Home,
    },
    {
        label: tenantName.value,
        href: null,
    },
])

const formatDateTime = (date) => {
    if (!date) return '-'

    const parsedDate = new Date(date)

    if (Number.isNaN(parsedDate.getTime())) return '-'

    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(parsedDate)
}

const copyToClipboard = async (text) => {
    if (!text) {
        showToast('Link não encontrado.', 'error')
        return
    }

    try {
        await navigator.clipboard.writeText(text)
        showToast('Link copiado com sucesso!', 'success')
    } catch {
        showToast('Não foi possível copiar o link.', 'error')
    }
}

const generateDetail = (tenantId) => {
    if (!tenantId) {
        showToast('Identificador da página não encontrado.', 'error')
        return
    }

    isGeneratingDetail.value = true

    router.get(
        route('pagina.configuracao.generate.detail', tenantId),
        {},
        {
            preserveScroll: true,

            onSuccess: () => {
                showToast('Configuração gerada com sucesso!', 'success')
            },

            onError: (errors) => {
                const message =
                    Object.values(errors)?.[0] ||
                    'Erro ao gerar configuração.'

                showToast(message, 'error')
            },

            onFinish: () => {
                isGeneratingDetail.value = false
            },
        },
    )
}

const syncForms = (selected) => {
    if (!props.tenant?.id) {
        showToast('Tenant não encontrado.', 'error')
        return
    }

    if (!selected?.length) {
        showToast('Selecione ao menos um formulário.', 'warning')
        return
    }

    isSavingForms.value = true

    router.post(
        route('pagina.configuracao.forms', props.tenant.id),
        {
            forms: selected,
        },
        {
            preserveScroll: true,

            onSuccess: () => {
                showToast('Formulários vinculados com sucesso!', 'success')
                dialogOpen.value = false
                selectedFormIds.value = []

                router.reload({
                    only: ['tenant', 'forms', 'fomrs_tenants'],
                    preserveScroll: true,
                })
            },

            onError: (errors) => {
                const message =
                    Object.values(errors)?.[0] ||
                    'Erro ao vincular formulários.'

                showToast(message, 'error')
            },

            onFinish: () => {
                isSavingForms.value = false
            },
        },
    )
}

const handleUpdateExpiresAt = (payload) => {
    router.put(
        route('pagina.configuracao.expires-at', payload.id),
        {
            expires_at: payload.expires_at,
        },
        {
            preserveScroll: true,

            onSuccess: () => {
                showToast('Data de expiração atualizada com sucesso!', 'success')

                router.reload({
                    only: ['tenant', 'forms', 'fomrs_tenants'],
                    preserveScroll: true,
                })
            },

            onError: (errors) => {
                const message =
                    Object.values(errors)?.[0] ||
                    'Erro ao atualizar data de expiração.'

                showToast(message, 'error')
            },
        },
    )
}

const openRemoveLinkDialog = (item) => {
    selectedFormToRemove.value = item
    confirmDialogOpen.value = true
}

const closeRemoveLinkDialog = () => {
    confirmDialogOpen.value = false
    selectedFormToRemove.value = null
}

const openSmsModal = (template = null) => {
    smsModalTemplate.value = template
    smsModalOpen.value = true
}

const closeSmsModal = () => {
    smsModalOpen.value = false
    smsModalTemplate.value = null
}

const deleteSmsTemplate = (template) => {
    smsDeleteItem.value = template
    smsDeleteModal.value = true
}

const closeSmsDeleteModal = () => {
    smsDeleteModal.value = false
    smsDeleteItem.value = null
}

const confirmDeleteSmsTemplate = async () => {
    const template = smsDeleteItem.value
    if (!template) return

    isDeletingSms.value = true
    try {
        const response = await fetch(route('forms.sms-templates.destroy', template.id), {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] || ''),
            },
        })
        const data = await response.json()
        if (response.ok) {
            showToast(data.message || 'Template removido com sucesso.', 'success')
            closeSmsDeleteModal()
            router.reload({ only: ['smsTemplates'] })
        } else {
            showToast(data.message || 'Erro ao remover template.', 'error')
        }
    } catch {
        showToast('Erro ao remover template. Tente novamente.', 'error')
    } finally {
        isDeletingSms.value = false
    }
}

const toggleStatusFormularioDinamico = () => {
    if (!props.tenant?.id) {
        showToast('Tenant não encontrado.', 'error')
        return
    }

    isTogglingStatus.value = true

    router.put(
        route('pagina.configuracao.status-formulario-dinamico', props.tenant.id),
        {},
        {
            preserveScroll: true,

            onSuccess: () => {
                showToast('Status atualizado com sucesso!', 'success')
                router.reload({
                    only: ['statusFormularioDinamico'],
                    preserveScroll: true,
                })
            },

            onError: () => {
                showToast('Erro ao atualizar status.', 'error')
            },

            onFinish: () => {
                isTogglingStatus.value = false
            },
        },
    )
}

const syncTelemedicina = () => {
    if (!props.tenant?.id) {
        showToast('Tenant não encontrado.', 'error')
        return
    }

    isSavingTelemedicina.value = true

    router.put(
        route('pagina.configuracao.telemedicina', props.tenant.id),
        {
            enabled: true,
            questions: selectedTelemedicinaIds.value,
        },
        {
            preserveScroll: true,

            onSuccess: () => {
                showToast('Telemedicina atualizada com sucesso!', 'success')
                selectedTelemedicinaIds.value = []
                router.reload({
                    only: ['tenant', 'telemedicinaEnabled', 'telemedicinaQuestions', 'telemedicinaVinculados'],
                    preserveScroll: true,
                })
            },

            onError: () => {
                showToast('Erro ao atualizar telemedicina.', 'error')
            },

            onFinish: () => {
                isSavingTelemedicina.value = false
            },
        },
    )
}

const unlinkTelemedicina = (item) => {
    telemedicinaUnlinkItem.value = item
    telemedicinaUnlinkModal.value = true
}

const closeUnlinkTelemedicina = () => {
    telemedicinaUnlinkModal.value = false
    telemedicinaUnlinkItem.value = null
}

const confirmUnlinkTelemedicina = () => {
    const item = telemedicinaUnlinkItem.value
    if (!props.tenant?.id || !item) return

    isUnlinkingTelemedicina.value = true

    router.delete(
        route('pagina.configuracao.telemedicina.unlink', { tenant: props.tenant.id, telemedicinaTenant: item.id }),
        {
            preserveScroll: true,

            onSuccess: () => {
                showToast('Item desvinculado com sucesso!', 'success')
                closeUnlinkTelemedicina()
                router.reload({
                    only: ['telemedicinaVinculados'],
                    preserveScroll: true,
                })
            },

            onError: () => {
                showToast('Erro ao desvincular item.', 'error')
            },

            onFinish: () => {
                isUnlinkingTelemedicina.value = false
            },
        },
    )
}

const searchSiprov = async (page = 1) => {
    isSearchingSiprov.value = true
    siprovError.value = null
    try {
        const params = new URLSearchParams()
        if (siprovSearch.value) params.append('q', siprovSearch.value)
        if (page > 1) params.append('pagina', page)

        const response = await fetch(route('pagina.configuracao.telemedicina.searchSiprov') + '?' + params.toString())
        if (!response.ok) {
            throw new Error('Erro ao buscar associados.')
        }
        const data = await response.json()

        let itens = data.itens ?? data
        if (Array.isArray(itens) && itens.length === 1 && itens[0].itens) {
            itens = itens[0].itens
        }
        siprovResults.value = Array.isArray(itens) ? itens : []

        siprovPage.value = data.paginaAtual ?? 1
        siprovHasNext.value = data.proximaPagina ?? false
        siprovTotal.value = data.quantidade ?? siprovResults.value.length
    } catch {
        siprovResults.value = []
        siprovError.value = 'Não foi possível conectar à SIPROV. Tente novamente.'
    } finally {
        isSearchingSiprov.value = false
    }
}

const goToSiprovPage = (page) => {
    if (page < 1) return
    searchSiprov(page)
}

const openSiprovModal = () => {
    siprovModalOpen.value = true
    siprovSelected.value = []
    siprovError.value = null
    siprovPage.value = 1
    searchSiprov(1)
}

const getSiprovKey = (item) => `${item.codPessoa}-${item.codBeneficio}`

const toggleSiprovItem = (item) => {
    const key = getSiprovKey(item)
    const idx = siprovSelected.value.indexOf(key)
    if (idx >= 0) {
        siprovSelected.value.splice(idx, 1)
    } else {
        siprovSelected.value.push(key)
    }
}

const toggleSelectAllSiprov = () => {
    if (siprovSelected.value.length === siprovResults.value.length) {
        siprovSelected.value = []
    } else {
        siprovSelected.value = siprovResults.value.map(r => getSiprovKey(r))
    }
}

const vincularSiprov = () => {
    if (!props.tenant?.id || !siprovSelected.value.length) return

    isSavingSiprov.value = true

    const selectedItems = siprovResults.value
        .filter(r => siprovSelected.value.includes(getSiprovKey(r)))
        .map(({ codPessoa, nomePessoa, cpfCnpj, planos, codBeneficio }) => ({
            codPessoa, nomePessoa, cpfCnpj, planos, codBeneficio
        }))

    router.put(
        route('pagina.configuracao.telemedicina', props.tenant.id),
        {
            enabled: true,
            siprov_items: selectedItems,
        },
        {
            preserveScroll: true,

            onSuccess: () => {
                showToast('Itens SIPROV vinculados com sucesso!', 'success')
                siprovModalOpen.value = false
                siprovSelected.value = []
                router.reload({
                    only: ['telemedicinaVinculados'],
                    preserveScroll: true,
                })
            },

            onError: () => {
                showToast('Erro ao vincular itens SIPROV.', 'error')
            },

            onFinish: () => {
                isSavingSiprov.value = false
            },
        },
    )
}

const confirmRemoveLink = () => {
    if (!selectedFormToRemove.value?.id) {
        showToast('Formulário vinculado não encontrado.', 'error')
        return
    }

    isRemoving.value = true

    router.delete(
        route('pagina.configuracao.unlink', selectedFormToRemove.value.id),
        {
            preserveScroll: true,

            onSuccess: () => {
                showToast('Formulário desvinculado com sucesso!', 'success')
                closeRemoveLinkDialog()

                router.reload({
                    only: ['tenant', 'forms', 'fomrs_tenants'],
                    preserveScroll: true,
                })
            },

            onError: () => {
                showToast('Erro ao remover vínculo.', 'error')
            },

            onFinish: () => {
                isRemoving.value = false
            },
        },
    )
}
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
                    </div>

                    <!-- Dados complementares -->
                    <div v-if="activeTab === 'details' && detail" class="grid grid-cols-1 md:grid-cols-2 gap-5">
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

                    <!-- Responsável -->
                    <div v-if="activeTab === 'responsible' && user">
                        <div class="max-w-md mx-auto text-center rounded-2xl border border-gray-100 bg-gray-50 p-6">
                            <UserBadge :user="user" size="lg" show-email />
                        </div>
                    </div>

                    <!-- Domínios -->
                    <div v-if="activeTab === 'domains'" class="space-y-3">
                        <div v-for="domain in domains" :key="domain.id || domain.domain"
                            class="p-3 rounded-xl bg-gray-50 border border-gray-200 break-words">
                            {{ domain.domain }}
                        </div>

                        <div v-if="!domains.length" class="text-sm text-gray-500 text-center py-10">
                            Nenhum domínio cadastrado.
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
                                <p class="text-sm text-gray-500 mt-1">Gerencie as mensagens enviadas por SMS aos pacientes.</p>
                            </div>
                            <Button variant="primary" @click="openSmsModal()">
                                <Plus class="w-4 h-4 mr-1" />
                                Novo Template
                            </Button>
                        </div>

                        <div v-if="props.smsTemplates.length === 0"
                            class="bg-white p-12 rounded-xl border border-gray-200 text-center">
                            <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                <MessageSquare class="w-8 h-8 text-gray-400" />
                            </div>
                            <p class="text-lg font-medium text-gray-900">Nenhum template SMS</p>
                            <p class="text-sm text-gray-500 mt-1">Crie um template para enviar mensagens automáticas aos pacientes.</p>
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
                                            <span>Atualizado: {{ new Date(template.updated_at).toLocaleDateString('pt-BR') }}</span>
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
                            <p class="text-sm text-gray-500 mt-1">Configure comportamentos dinâmicos e envio de SMS para este tenant.</p>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                            <!-- Status Formulário Dinâmico -->
                            <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="p-3 bg-purple-50 rounded-lg">
                                            <Settings class="w-6 h-6 text-purple-600" />
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-semibold text-gray-900">Status Formulário Dinâmico</h4>
                                            <p class="text-xs text-gray-500 mt-0.5">Atualização automática do paciente</p>
                                        </div>
                                    </div>
                                    <button
                                        @click="toggleStatusFormularioDinamico"
                                        :disabled="isTogglingStatus"
                                        :class="[
                                            'relative inline-flex h-7 w-12 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2',
                                            statusFormularioDinamico ? 'bg-purple-600' : 'bg-gray-200',
                                            isTogglingStatus ? 'opacity-50 cursor-not-allowed' : ''
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out',
                                                statusFormularioDinamico ? 'translate-x-5' : 'translate-x-0'
                                            ]"
                                        />
                                    </button>
                                </div>
                                <p class="text-sm text-gray-500 mt-4">
                                    Quando ativado, o formulário vinculado ao paciente será atualizado dinamicamente conforme as respostas recebidas.
                                </p>
                                <div class="mt-3">
                                    <span :class="[
                                        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium',
                                        statusFormularioDinamico
                                            ? 'bg-purple-100 text-purple-700 border border-purple-200'
                                            : 'bg-gray-100 text-gray-500 border border-gray-200'
                                    ]">
                                        <span :class="['w-1.5 h-1.5 rounded-full', statusFormularioDinamico ? 'bg-purple-500' : 'bg-gray-400']" />
                                        {{ statusFormularioDinamico ? 'Ativado' : 'Desativado' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Template SMS Vinculado -->
                            <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
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
                                        <div :class="['w-3 h-3 rounded-full shrink-0', template.is_active ? 'bg-green-500' : 'bg-gray-300']" />
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

                    <!-- Telemedicina -->
                    <div v-if="activeTab === 'telemedicina'" class="rounded-2xl border border-gray-100 bg-white p-5 space-y-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-base font-semibold flex items-center gap-2 text-gray-800">
                                    <HeartPulse class="w-5 h-5 text-red-500" />
                                    Telemedicina
                                </h2>
                                <p class="text-sm text-gray-600 mt-0.5">Gerencie os vínculos de telemedicina deste tenant.</p>
                            </div>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold"
                                :class="telemedicinaVinculados.length ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-gray-50 text-gray-500 border border-gray-200'">
                                <CheckCircle v-if="telemedicinaVinculados.length" class="w-4 h-4" />
                                <Users v-else class="w-4 h-4" />
                                {{ telemedicinaVinculados.length }} associado{{ telemedicinaVinculados.length !== 1 ? 's' : '' }}
                            </span>
                        </div>

                        <!-- Buscar / Filtrar -->
                        <div class="space-y-3">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                                <input
                                    v-model="siprovSearch"
                                    type="text"
                                    placeholder="Filtrar vinculados por nome, CPF ou plano..."
                                    class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-gray-200 bg-gray-50 text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all"
                                />
                                <button v-if="siprovSearch" type="button" @click="siprovSearch = ''"
                                    class="absolute inset-y-0 right-2 flex items-center text-gray-400 hover:text-gray-600 transition-colors">
                                    <X class="w-4 h-4" />
                                </button>
                            </div>

                            <div v-if="siprovSearch && telemedicinaVinculados.length" class="text-xs text-gray-500">
                                {{ filteredTelemedicinaVinculados.length }} de {{ telemedicinaVinculados.length }} resultado(s)
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
                                <button
                                    @click="unlinkTelemedicina(item)"
                                    class="shrink-0 flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-all opacity-0 group-hover:opacity-100 focus:opacity-100"
                                    title="Desvincular"
                                >
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
                            <p class="text-sm font-medium text-gray-700 mt-3" v-if="telemedicinaVinculados.length">Nenhum resultado para "{{ siprovSearch }}"</p>
                            <p class="text-sm font-medium text-gray-700 mt-3" v-else>Nenhum associado vinculado</p>
                            <p class="text-xs text-gray-400 mt-1" v-if="telemedicinaVinculados.length">Tente outro termo de busca.</p>
                            <p class="text-xs text-gray-400 mt-1" v-else>Adicione associados da SIPROV para habilitar a telemedicina.</p>
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

        <ConfirmDeleteModal :show="confirmDialogOpen" title="Remover vínculo"
            message="Deseja remover esse vínculo?" confirm-text="Sim, remover"
            cancel-text="Cancelar" :isProcessing="isRemoving" @close="closeRemoveLinkDialog" @confirm="confirmRemoveLink" />

        <ConfirmDeleteModal :show="telemedicinaUnlinkModal" title="Desvincular item"
            :message="telemedicinaUnlinkItem ? 'Deseja desvincular ' + telemedicinaUnlinkItem.data?.title + '?' : 'Deseja desvincular este item?'"
            confirm-text="Sim, desvincular" cancel-text="Cancelar"
            :isProcessing="isUnlinkingTelemedicina"
            @close="closeUnlinkTelemedicina" @confirm="confirmUnlinkTelemedicina" />

        <ConfirmDeleteModal :show="smsDeleteModal" title="Remover template SMS"
            :message="smsDeleteItem ? `Deseja remover o template '${smsDeleteItem.name}'?` : 'Deseja remover este template?'"
            confirm-text="Sim, remover" cancel-text="Cancelar"
            :isProcessing="isDeletingSms"
            @close="closeSmsDeleteModal" @confirm="confirmDeleteSmsTemplate" />

        <SmsTemplateModal :show="smsModalOpen" :template="smsModalTemplate" event="patient.created"
            :tenants="allTenants || []"
            @close="closeSmsModal" />

        <!-- SIPROV Modal -->
        <div v-if="siprovModalOpen"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 backdrop-blur-sm">
            <div class="mx-4 w-full max-w-6xl max-h-[80vh] rounded-2xl bg-white shadow-2xl border border-gray-100 flex flex-col">
                <div class="flex items-center justify-between p-6 border-b border-gray-100">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Vincular SIPROV</h3>
                        <p class="text-sm text-gray-500 mt-0.5">Busque e selecione registros do SIPROV para vincular a este tenant.</p>
                    </div>
                    <button
                        @click="siprovModalOpen = false"
                        class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-6 border-b border-gray-100">
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <input
                            v-model="siprovSearch"
                            type="text"
                            placeholder="Buscar por nome, CPF, e-mail ou código de integração..."
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all"
                            @keyup.enter="searchSiprov(1)"
                        />
                        <Button variant="primary" size="sm" class="absolute right-1.5 top-1/2 -translate-y-1/2" :disabled="isSearchingSiprov" @click="searchSiprov(1)">
                            <Loader2 v-if="isSearchingSiprov" class="w-4 h-4 animate-spin" />
                            <Search v-else class="w-4 h-4" />
                        </Button>
                    </div>
                </div>

                <div v-if="siprovError" class="mx-6 mt-3 p-4 rounded-lg text-sm font-medium bg-amber-100 text-amber-800 border border-amber-200">
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
                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold bg-gray-100 text-gray-500">3</span>
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
                                        <input
                                            type="checkbox"
                                            :checked="siprovSelected.length === siprovResults.length"
                                            @change="toggleSelectAllSiprov"
                                            class="w-4 h-4 text-cyan-600 bg-gray-100 border-gray-300 rounded focus:ring-cyan-500"
                                        />
                                    </th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">#</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">Associado</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">CPF/CNPJ</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">Plano</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">Benefício</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">Cadastro</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">Situação</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-for="item in siprovResults" :key="getSiprovKey(item)"
                                    class="hover:bg-gray-50 transition-colors cursor-pointer"
                                    :class="{ 'bg-cyan-50 hover:bg-cyan-100': siprovSelected.includes(getSiprovKey(item)) }"
                                    @click="toggleSiprovItem(item)"
                                >
                                    <td class="px-4 py-3" @click.stop>
                                        <input
                                            type="checkbox"
                                            :checked="siprovSelected.includes(getSiprovKey(item))"
                                            @change="toggleSiprovItem(item)"
                                            class="w-4 h-4 text-cyan-600 bg-gray-100 border-gray-300 rounded focus:ring-cyan-500"
                                        />
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-sm text-gray-900 font-mono">
                                        #{{ item.codPessoa }}
                                    </td>
                                    <td class="px-3 py-3 text-sm">
                                        <div class="font-medium text-gray-900 group-hover:text-cyan-600 transition-colors">
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
                                        <div v-if="item.dataAdesao" class="text-xs text-gray-400">Adesão: {{ item.dataAdesao }}</div>
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
                                <Button variant="outline" size="sm" :disabled="siprovPage <= 1 || isSearchingSiprov" @click="goToSiprovPage(siprovPage - 1)">
                                    Anterior
                                </Button>
                                <Button variant="outline" size="sm" :disabled="!siprovHasNext || isSearchingSiprov" @click="goToSiprovPage(siprovPage + 1)">
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

                <div class="flex items-center justify-between p-4 border-t border-gray-100 bg-gray-50/50"
                    :class="{ 'bg-cyan-50/70 border-cyan-200': siprovSelected.length > 0 }">
                    <span class="text-sm font-medium" :class="siprovSelected.length > 0 ? 'text-cyan-800' : 'text-gray-500'">
                        <template v-if="siprovSelected.length > 0">
                            <CheckCircle class="w-4 h-4 inline-block mr-1.5" />
                            {{ siprovSelected.length }} associado{{ siprovSelected.length !== 1 ? 's' : '' }} selecionado{{ siprovSelected.length !== 1 ? 's' : '' }}
                        </template>
                        <template v-else>
                            {{ siprovResults.length }} resultado(s)
                        </template>
                    </span>
                    <div class="flex items-center gap-2">
                        <Button variant="secondary" @click="siprovModalOpen = false">
                            Cancelar
                        </Button>
                        <Button variant="primary" :disabled="!siprovSelected.length || isSavingSiprov" @click="vincularSiprov">
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
