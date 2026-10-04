<script setup>
import CentralAdminLayout from '@/Layouts/CentralAdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Pencil, Trash2, Plus, Search, X, Building2, ShieldAlert, Globe, Database, User, Power, PowerOff, Palette } from 'lucide-vue-next';
import Button from '@/Components/ui/button/Button.vue';
import ConfirmDeleteModal from '@/Components/ConfirmDeleteModal.vue';
import PomponeteLink from '@/Components/PomponeteLink.vue';
import DetailCard from '@/Components/DetailCard.vue';
import PaginationSimple from '@/Components/PaginationSimple.vue'
import CorParceiroModal from '@/Components/CorParceiroModal.vue';
import { formatDate, formatDateTime } from '@/Composables/Pagina/helpers';
import { usePaginaIndex } from '@/Composables/Pagina/usePaginaIndex';

const props = defineProps({
    tenants: {
        type: Object,
        default: () => ({
            data: [],
            links: [],
            from: 0,
            to: 0,
            total: 0
        })
    },
    filters: {
        type: Object,
        default: () => ({ search: '', plano: '' })
    },
    // Catálogo de planos para o filtro: [{ value, label }]
    planos: {
        type: Array,
        default: () => []
    },
    // Parceiros para o filtro: [{ value, label }]
    tenantsOpcoes: {
        type: Array,
        default: () => []
    },
    // Cards: { contratos, total, ativos, inativos, planos: [{ value, label, parceiros, vagas, beneficiarios }] }
    totais: {
        type: Object,
        default: null
    }
});

const {
    auth,
    can,
    canManage,
    flashMessage,
    flashType,
    search,
    planoFilter,
    tenantFilter,
    searchInput,
    deleteModal,
    statusModal,
    bulkDisableModal,
    tenantList,
    paginationLinks,
    hasTenants,
    hasSearch,
    hasActiveFilters,
    clearSearch,
    filtrarPlano,
    openDeleteModal,
    closeDeleteModal,
    confirmDelete,
    openStatusModal,
    closeStatusModal,
    confirmToggleStatus,
    openBulkDisableModal,
    closeBulkDisableModal,
    confirmBulkDisable,
    getTenantDomain,
    getTenantStatus,
    getStatusClass,
    getStatusLabel,
    getInitials,
    navigateTo,
} = usePaginaIndex(props);

const cardsResumo = computed(() => [
    { label: 'Planos contratados', total: props.totais?.contratos ?? 0, cor: 'text-cyan-700' },
    { label: 'Beneficiários cadastrados', total: props.totais?.total ?? 0, cor: 'text-gray-900' },
    { label: 'Ativos', total: props.totais?.ativos ?? 0, cor: 'text-green-700' },
    { label: 'Inativos', total: props.totais?.inativos ?? 0, cor: 'text-red-700' },
]);

const cardPlano = (ativo) => [
    'rounded-xl border p-4 text-left transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500',
    ativo ? 'border-cyan-300 bg-cyan-50' : 'border-gray-200 bg-white hover:bg-gray-50',
];

const beneficiariosNoPlano = (item, codPlano) =>
    item.beneficiarios?.planos?.find(p => p.value === codPlano)?.total ?? 0;

// Cor indicativa do parceiro (usada nos gráficos do dashboard).
const corModal = ref({ show: false, tenant: null });
const abrirCorModal = (item) => {
    corModal.value = {
        show: true,
        tenant: { id: item.id, indicativo_cor: item.indicativo_cor, nome: item.details?.[0]?.descricao },
    };
};
</script>
<template>
    <CentralAdminLayout>
        <div class="flex items-center justify-between w-full">

            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 uppercase tracking-wide">
                    Página de Parceiros ({{ props.tenantsOpcoes.length }})
                </h2>
            </div>
            <div v-if="canManage"
                class="flex items-center gap-2 text-xs text-cyan-600 bg-cyan-50 px-3 py-1 rounded-full">
                <ShieldAlert class="w-4 h-4" />
                <span>Modo Administrador</span>
            </div>
        </div>
        <div class="py-6">
            <div v-if="flashMessage" :class="[
                'mb-4 p-4 rounded-lg text-sm font-medium',
                flashType === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'
            ]">
                {{ flashMessage }}
            </div>
            <div class="mx-auto  space-y-4">
                <!-- Totalizadores (por plano: clique para filtrar) -->
                <div v-if="props.totais" class="space-y-4">
                    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                        <div v-for="item in cardsResumo" :key="item.label"
                            class="rounded-xl border border-gray-200 bg-white p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ item.label }}</p>
                            <p class="mt-1 text-2xl font-bold" :class="item.cor">{{ item.total }}</p>
                        </div>
                    </div>
                    <div v-if="props.totais.planos.length">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Beneficiários por plano</p>
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                            <button v-for="plano in props.totais.planos" :key="plano.value" type="button"
                                :aria-pressed="planoFilter === plano.value" :class="cardPlano(planoFilter === plano.value)"
                                @click="filtrarPlano(plano.value)">
                                <p class="truncate text-xs font-medium uppercase tracking-wide text-gray-500" :title="plano.label">
                                    {{ plano.label }}
                                </p>
                                <p class="mt-1 text-2xl font-bold text-cyan-700">{{ plano.beneficiarios }}</p>
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ plano.parceiros }} parceiro(s) · {{ plano.vagas }} vaga(s)
                                </p>
                            </button>
                        </div>
                    </div>
                </div>
                <div
                    class="flex flex-col lg:flex-row gap-3 justify-between items-start lg:items-center bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                        <!-- Campo de busca -->
                        <div class="relative w-full sm:w-100">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <Search class="h-5 w-5 text-gray-400" />
                            </div>
                            <input ref="searchInput" v-model="search" type="text"
                                placeholder="Buscar por ID ou domínio..."
                                class="block w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm transition-shadow"
                                @keyup.esc="clearSearch" />
                            <button v-if="hasSearch" @click="clearSearch"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors">
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                        <select v-model="tenantFilter" aria-label="Filtrar por parceiro"
                            class="border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition-all">
                            <option value="">Todos os parceiros</option>
                            <option v-for="tenant in props.tenantsOpcoes" :key="tenant.value" :value="tenant.value">
                                {{ tenant.label }}
                            </option>
                        </select>
                        <select v-model="planoFilter" aria-label="Filtrar por plano contratado"
                            class="border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition-all">
                            <option value="">Todos os planos</option>
                            <option v-for="plano in props.planos" :key="plano.value" :value="plano.value">
                                {{ plano.label }}
                            </option>
                        </select>
                        <!-- <button v-if="hasActiveFilters" @click="clearSearch"
                            class="flex items-center gap-1 px-3 py-1 text-xs font-medium text-cyan-700 bg-cyan-100 rounded-full hover:bg-cyan-200 transition-colors">
                            <X class="w-3 h-3" />
                            Limpar filtros
                        </button> -->
                    </div>
                    <template v-if="can.create">
                        <div class="flex items-center gap-2">
                            <Button
                                class="flex items-center gap-2 rounded-xl bg-cyan-500 hover:bg-cyan-600 px-5 py-2.5 text-white font-semibold shadow-md transition-all hover:shadow-lg hover:scale-[1.02] active:scale-[0.98]"
                                @click="navigateTo('pagina.create')">
                                <Plus class="w-4 h-4" />
                                Adicionar Página
                            </Button>
                            <Button variant="outline"
                                class="flex items-center gap-2 rounded-xl border-red-300 text-red-600 hover:bg-red-50 px-5 py-2.5 font-semibold transition-all"
                                @click="openBulkDisableModal">
                                <Power class="w-4 h-4" />
                                Desativar todas
                            </Button>
                        </div>
                    </template>
                    <div v-else
                        class="flex items-center gap-2 px-5 py-2.5 text-gray-400 text-sm bg-gray-100 rounded-xl cursor-not-allowed"
                        title="Você não tem permissão para criar páginas">
                        <ShieldAlert class="w-4 h-4" />
                        Sem permissão
                    </div>
                </div>
                <div v-if="hasActiveFilters && hasTenants"
                    class="flex items-center gap-2 text-sm text-gray-600 bg-gray-50 p-3 rounded-lg">
                    <Search class="w-4 h-4 text-cyan-600" />
                    <span>
                        Mostrando {{ props.tenants.total }} resultado(s)
                        <template v-if="search">
                            para "<span class="font-semibold text-cyan-700">{{ search }}</span>"
                        </template>
                    </span>
                </div>
                <div class="border rounded-xl border-gray-200 bg-white shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                        Tenant
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                        Domínio
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                        Criado em
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                        Planos contratados
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                        Beneficiários
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                        Status
                                    </th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                        Ações
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-for="item in tenantList" :key="item.id"
                                    class="hover:bg-gray-50 transition-colors group">
                                    <td class="px-6 py-4 text-sm">
                                        <DetailCard v-for="detail in item.details" :key="detail.id" :detail="detail" />
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        <div class="flex items-center gap-1.5">
                                            <PomponeteLink :url="item.url" :label="item.url" />
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        <time :title="formatDateTime(item.created_at)">
                                            {{ formatDate(item.created_at) }}
                                        </time>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <div v-if="item.planos_contratados?.length" class="space-y-1">
                                            <div v-for="plano in item.planos_contratados" :key="plano.value"
                                                class="flex items-center justify-between gap-3 text-xs">
                                                <span class="text-gray-700">{{ plano.label }}</span>
                                                <span class="text-gray-500 whitespace-nowrap"
                                                    title="Beneficiários no plano / contratados">
                                                    {{ beneficiariosNoPlano(item, plano.value) }} / {{ plano.quantidade }}
                                                </span>
                                            </div>
                                        </div>
                                        <span v-else class="text-xs text-gray-400">Nenhum plano</span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-xs">
                                        <div v-if="item.beneficiarios" class="space-y-0.5">
                                            <div class="text-gray-900 font-semibold">{{ item.beneficiarios.total }} cadastrados</div>
                                            <div class="text-green-700">{{ item.beneficiarios.ativos }} ativos</div>
                                            <div class="text-red-700">{{ item.beneficiarios.inativos }} inativos</div>
                                        </div>
                                        <span v-else class="text-gray-400">Indisponível</span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        <span
                                            :class="['inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border', getStatusClass(getTenantStatus(item))]">
                                            {{ getStatusLabel(getTenantStatus(item)) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                        <div class="flex justify-end gap-1">
                                            <button @click="navigateTo('pagina.show', item.id)"
                                                class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-all"
                                                title="Visualizar">
                                                <Building2 class="w-4 h-4" />
                                            </button>
                                            <button @click="navigateTo('pagina.users.index', item.id)"
                                                class="p-2 cursor-pointer text-cyan-600 hover:text-cyan-800 hover:bg-cyan-50 rounded-lg transition-all"
                                                title="Editar">
                                                <User class="w-4 h-4" />
                                            </button>
                                            <button @click="abrirCorModal(item)"
                                                class="relative p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-all"
                                                :title="item.indicativo_cor ? `Cor do parceiro: ${item.indicativo_cor}` : 'Cor do parceiro: padrão do sistema'">
                                                <Palette class="w-4 h-4" />
                                                <span class="absolute bottom-1 right-1 h-2 w-2 rounded-full ring-2 ring-white"
                                                    :class="item.indicativo_cor ? '' : 'bg-gray-300'"
                                                    :style="item.indicativo_cor ? { background: item.indicativo_cor } : {}" />
                                            </button>
                                            <button @click="openStatusModal(item)" :class="[
                                                'p-2 rounded-lg transition-all',
                                                item.status
                                                    ? 'text-orange-600 hover:text-orange-800 hover:bg-orange-50'
                                                    : 'text-green-600 hover:text-green-800 hover:bg-green-50'
                                            ]" :title="item.status ? 'Desativar' : 'Ativar'">
                                                <PowerOff v-if="item.status" class="w-4 h-4" />
                                                <Power v-else class="w-4 h-4" />
                                            </button>
                                            <!-- <button v-if="can.edit" @click="navigateTo('pagina.edit', item.id)"
                                                class="p-2 text-cyan-600 hover:text-cyan-800 hover:bg-cyan-50 rounded-lg transition-all"
                                                title="Editar">
                                                <Pencil class="w-4 h-4" />
                                            </button>
                                            <span v-else class="p-2 text-gray-300 cursor-not-allowed"
                                                title="Sem permissão para editar">
                                                <Pencil class="w-4 h-4" />
                                            </span> -->
                                            <button v-if="can.delete" @click="openDeleteModal(item)"
                                                class="p-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-all"
                                                title="Excluir">
                                                <Trash2 class="w-4 h-4" />
                                            </button>
                                            <span v-else class="p-2 text-gray-300 cursor-not-allowed"
                                                title="Sem permissão para excluir">
                                                <Trash2 class="w-4 h-4" />
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="!hasTenants" class="text-center py-16 text-gray-500">
                        <div v-if="hasActiveFilters" class="space-y-3">
                            <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center">
                                <Search class="w-8 h-8 text-gray-400" />
                            </div>
                            <p class="text-lg font-medium text-gray-900">Nenhum resultado encontrado</p>
                            <p class="text-sm text-gray-500 max-w-sm mx-auto">
                                Não encontramos tenants para "<span class="font-medium">{{ search }}</span>"
                            </p>
                            <Button variant="outline" size="sm" @click="clearSearch" class="mt-2">
                                <X class="w-4 h-4 mr-1" />
                                Limpar filtros
                            </Button>
                        </div>
                        <div v-else class="space-y-3">
                            <div class="w-16 h-16 mx-auto bg-cyan-50 rounded-full flex items-center justify-center">
                                <Building2 class="w-8 h-8 text-cyan-500" />
                            </div>
                            <p class="text-lg font-medium text-gray-900">Nenhum tenant cadastrado</p>
                            <p class="text-sm text-gray-500">Comece adicionando o primeiro tenant ao sistema</p>
                        </div>
                    </div>
                    <PaginationSimple :data="props.tenants" :links="paginationLinks" :has-data="hasTenants"
                        label="tenants" />
                </div>
            </div>
        </div>
        <ConfirmDeleteModal :show="deleteModal.show" :item-name="deleteModal.tenant?.id" title="Excluir Tenant"
            message="Tem certeza que deseja excluir este tenant?"
            warning-message="Todos os dados associados serão permanentemente removidos. Esta ação não pode ser desfeita."
            confirm-text="Sim, Excluir" cancel-text="Cancelar" :is-processing="deleteModal.isProcessing"
            variant="danger" @close="closeDeleteModal" @confirm="confirmDelete" />
        <ConfirmDeleteModal :show="statusModal.show"
            :title="statusModal.tenant?.status ? 'Desativar Parceiro' : 'Ativar Parceiro'"
            :message="statusModal.tenant?.status ? 'Tem certeza que deseja desativar este parceiro?' : 'Tem certeza que deseja ativar este parceiro?'"
            :warning-message="statusModal.tenant?.status ? 'Ao desativar, o parceiro ficará inacessível para os usuários.' : 'Ao ativar, o parceiro voltará a ficar acessível para os usuários.'"
            :confirm-text="statusModal.tenant?.status ? 'Sim, Desativar' : 'Sim, Ativar'" cancel-text="Cancelar"
            :is-processing="statusModal.isProcessing" variant="warning" @close="closeStatusModal"
            @confirm="confirmToggleStatus" />

        <ConfirmDeleteModal :show="bulkDisableModal.show" title="Desativar todas as páginas"
            message="Tem certeza que deseja desativar TODAS as páginas?"
            warning-message="Apenas páginas ativas serão afetadas. Esta ação pode ser revertida manualmente."
            confirm-text="Sim, Desativar todas" cancel-text="Cancelar" :is-processing="bulkDisableModal.isProcessing"
            variant="danger" @close="closeBulkDisableModal" @confirm="confirmBulkDisable" />

        <CorParceiroModal :show="corModal.show" :tenant="corModal.tenant" @close="corModal.show = false" />
    </CentralAdminLayout>
</template>
