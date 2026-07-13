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
import FormLinkedCard from '@/Components/Cards/FormLinkedCard.vue'
import { showToast } from '@/Utils/toast'
import ConfirmDeleteModal from '@/Components/ConfirmDeleteModal.vue'

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
})

const activeTab = ref('overview')

const isGeneratingDetail = ref(false)
const dialogOpen = ref(false)
const selectedFormIds = ref([])
const isSavingForms = ref(false)

const confirmDialogOpen = ref(false)
const selectedFormToRemove = ref(null)
const isRemoving = ref(false)

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
                            <FormLinkedCard v-for="item in fomrs_tenants" :key="item.id" :item="item"
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
                </div>
            </div>
        </div>

        <FormSelectorDialog v-model:open="dialogOpen" v-model="selectedFormIds" :forms="availableForms"
            @confirm="syncForms" />

        <ConfirmDeleteModal :show="confirmDialogOpen" title="Remover vínculo"
            message="Deseja remover esse vínculo?" confirm-text="Sim, remover"
            cancel-text="Cancelar" :isProcessing="isRemoving" @close="closeRemoveLinkDialog" @confirm="confirmRemoveLink" />
    </CentralAdminLayout>
</template>
