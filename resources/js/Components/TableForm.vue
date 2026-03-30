<script setup>
import { ref, computed } from "vue";
import {
    Pencil, Trash2, Eye, Globe, Lock, FileText, Calendar, User, Play,
    ExternalLink, Pause, Square, AlertCircle, EyeOff
} from "lucide-vue-next";
import { router } from "@inertiajs/vue3";
import Table from "./ui/table/Table.vue";
import TableHeader from "./ui/table/TableHeader.vue";
import TableRow from "./ui/table/TableRow.vue";
import TableHead from "./ui/table/TableHead.vue";
import TableBody from "./ui/table/TableBody.vue";
import TableCell from "./ui/table/TableCell.vue";
import ConfirmDeleteModal from '@/Components/ConfirmDeleteModal.vue';
import ConfirmActionModal from '@/Components/ConfirmActionModal.vue';

const props = defineProps({
    forms: {
        type: Object,
        required: true,
        default: () => ({ data: [] }),
    },
    can: {
        type: Object,
        default: () => ({ edit: false, delete: false, toggleVisibility: false }),
    },
    activatingId: {
        type: [Number, String],
        default: null,
    },
    deletingId: {
        type: [Number, String],
        default: null,
    },
    togglingVisibilityId: {
        type: [Number, String],
        default: null,
    },
});

const emit = defineEmits([
    "edit-form",
    "view-form",
    "view-form-public",
    "activate-form",
    "delete-form",
    "toggle-visibility"
]);

const deleteModal = ref({
    show: false,
    form: null,
    isProcessing: false
});

const actionModal = ref({
    show: false,
    form: null,
    action: '',
    isProcessing: false,
});

const visibilityModal = ref({
    show: false,
    form: null,
    isProcessing: false,
});

const STATUS_CONFIG = {
    'rascunho': { label: "em edição (rascunho)", color: 'gray' },
    'ativo': { label: "ativo", color: 'green' },
    'pausado': { label: "pausado", color: 'yellow' },
    'encerrado': { label: "encerrado", color: 'red' },
};

const getStatusConfig = (status) => STATUS_CONFIG[status] || { label: status, color: 'gray' };

const actionModalConfig = computed(() => {
    const form = actionModal.value.form;
    const action = actionModal.value.action;
    if (!form || !action) return {};

    const configs = {
        activate: {
            title: 'Ativar Formulário',
            description: `Deseja ativar o formulário "${form.title}"?\n\nStatus atual: ${getStatusConfig(form.status).label}\n\nApós ativação, ele poderá receber respostas públicas.`,
            confirmText: 'Sim, Ativar',
            confirmVariant: 'success',
            icon: 'check',
            processingText: 'Ativando...',
            nextStatus: 'ativo'
        },
        pause: {
            title: 'Pausar Formulário',
            description: `Deseja pausar o formulário "${form.title}"?\n\nStatus atual: ativo\n\nO formulário não receberá novas respostas até ser reativado.`,
            confirmText: 'Sim, Pausar',
            confirmVariant: 'warning',
            icon: 'warning',
            processingText: 'Pausando...',
            nextStatus: 'pausado'
        },
        close: {
            title: 'Encerrar Formulário',
            description: `Deseja encerrar o formulário "${form.title}"?\n\nStatus atual: ${getStatusConfig(form.status).label}\n\n⚠️ Atenção: Esta ação não pode ser desfeita!`,
            confirmText: 'Sim, Encerrar',
            confirmVariant: 'danger',
            icon: 'warning',
            processingText: 'Encerrando...',
            nextStatus: 'encerrado'
        },
    };
    return configs[action] || {};
});

const visibilityModalConfig = computed(() => {
    const form = visibilityModal.value.form;
    if (!form) return {};

    const isPublic = form.is_public;
    return {
        title: isPublic ? 'Tornar Privado' : 'Tornar Público',
        description: isPublic
            ? `Deseja tornar o formulário "${form.title}" privado?\n\nEle não será mais acessível publicamente.`
            : `Deseja tornar o formulário "${form.title}" público?\n\nEle poderá ser acessado por qualquer pessoa com o link.`,
        confirmText: isPublic ? 'Sim, Tornar Privado' : 'Sim, Tornar Público',
        confirmVariant: isPublic ? 'warning' : 'success',
        icon: isPublic ? 'lock' : 'globe',
        processingText: 'Alterando...',
    };
});

const editForm = (form) => {
    emit("edit-form", form);
};

const viewForm = (form) => {
    emit("view-form", form);
};

const viewFormPublic = (form) => {
    if (form.status !== 'ativo') {
        emit('view-form-public', form);
        return;
    }
    const url = route('forms.public.show', form.slug);
    window.open(url, '_blank', 'noopener,noreferrer');
};

const openActionModal = (form, action) => {
    actionModal.value = {
        show: true,
        form: form,
        action: action,
        isProcessing: false,
    };
};

const closeActionModal = () => {
    actionModal.value.show = false;
    actionModal.value.form = null;
    actionModal.value.action = '';
    actionModal.value.isProcessing = false;
};

const confirmAction = () => {
    const form = actionModal.value.form;
    const config = actionModalConfig.value;
    if (!form || !config.nextStatus) return;

    actionModal.value.isProcessing = true;
    emit('activate-form', { ...form, _action: config.nextStatus });

    router.patch(
        route('forms.update-status', form.id),
        { status: config.nextStatus },
        {
            preserveScroll: true,
            onFinish: () => {
                closeActionModal();
            },
            onSuccess: () => {
            },
            onError: (errors) => {
                alert('Erro ao atualizar status: ' + Object.values(errors).join('\n'));
            }
        }
    );
};

const openVisibilityModal = (form) => {
    visibilityModal.value = {
        show: true,
        form: form,
        isProcessing: false,
    };
};

const closeVisibilityModal = () => {
    visibilityModal.value.show = false;
    visibilityModal.value.form = null;
    visibilityModal.value.isProcessing = false;
};

const confirmToggleVisibility = () => {
    const form = visibilityModal.value.form;
    if (!form) return;

    visibilityModal.value.isProcessing = true;
    emit('toggle-visibility', form);

    router.patch(
        route('forms.toggle-visibility', form.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                closeVisibilityModal();
            },
            onError: (errors) => {
                alert('Erro ao alterar visibilidade: ' + Object.values(errors).join('\n'));
            }
        }
    );
};

const openDeleteModal = (form) => {
    deleteModal.value = {
        show: true,
        form: form,
        isProcessing: false
    };
};

const closeDeleteModal = () => {
    deleteModal.value.show = false;
    deleteModal.value.form = null;
    deleteModal.value.isProcessing = false;
};

// ⭐ CONFIRMAR DELETE COM CALLBACKS - fecha modal no sucesso, mantém aberto no erro
const confirmDelete = () => {
    if (!deleteModal.value.form) return;
    deleteModal.value.isProcessing = true;

    // Emitir com callbacks para controlar o modal
    emit('delete-form', deleteModal.value.form, {
        onSuccess: () => {
            closeDeleteModal(); // ⭐ FECHA O MODAL NO SUCESSO
        },
        onError: () => {
            deleteModal.value.isProcessing = false; // ⭐ LIBERA PARA TENTAR NOVAMENTE
        }
    });
};

const isProcessingDelete = computed(() => {
    return props.deletingId === deleteModal.value.form?.id;
});

const isProcessingVisibility = computed(() => {
    return props.togglingVisibilityId === visibilityModal.value.form?.id;
});

const formatStatus = (status) => {
    const statuses = {
        rascunho: { label: "Rascunho", class: "bg-gray-100 text-gray-800" },
        ativo: { label: "Ativo", class: "bg-green-100 text-green-800" },
        pausado: { label: "Pausado", class: "bg-yellow-100 text-yellow-800" },
        encerrado: { label: "Encerrado", class: "bg-red-100 text-red-800" },
    };
    return statuses[status] || { label: status, class: "bg-gray-100 text-gray-800" };
};

const formatDate = (date) => {
    if (!date) return "-";
    return new Date(date).toLocaleDateString("pt-BR", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
    });
};

const truncate = (text, length = 50) => {
    if (!text) return "-";
    return text.length > length ? text.substring(0, length) + "..." : text;
};

const isProcessing = (formId) => {
    return props.activatingId === formId || props.deletingId === formId || props.togglingVisibilityId === formId;
};

const canActivate = (form) => ['rascunho', 'pausado'].includes(form.status);
const canPause = (form) => form.status === 'ativo';
const canClose = (form) => ['ativo', 'pausado'].includes(form.status);
</script>

<template>
    <div>
        <!-- MODAL DE AÇÃO (ATIVAR/PAUSAR/ENCERRAR) -->
        <ConfirmActionModal :show="actionModal.show" :title="actionModalConfig.title"
            :description="actionModalConfig.description" :confirm-text="actionModalConfig.confirmText"
            cancel-text="Cancelar" :confirm-variant="actionModalConfig.confirmVariant" :icon="actionModalConfig.icon"
            :is-processing="actionModal.isProcessing" :processing-text="actionModalConfig.processingText"
            @close="closeActionModal" @confirm="confirmAction" />

        <!-- MODAL DE VISIBILIDADE -->
        <ConfirmActionModal :show="visibilityModal.show" :title="visibilityModalConfig.title"
            :description="visibilityModalConfig.description" :confirm-text="visibilityModalConfig.confirmText"
            cancel-text="Cancelar" :confirm-variant="visibilityModalConfig.confirmVariant"
            :icon="visibilityModalConfig.icon" :is-processing="visibilityModal.isProcessing"
            :processing-text="visibilityModalConfig.processingText" @close="closeVisibilityModal"
            @confirm="confirmToggleVisibility" />

        <!-- MODAL DE DELETE -->
        <ConfirmDeleteModal :show="deleteModal.show" :item-name="deleteModal.form?.title" title="Excluir Formulário"
            message="Tem certeza que deseja excluir este formulário?"
            warning-message="Esta ação não pode ser desfeita e todas as respostas associadas serão perdidas."
            confirm-text="Sim, Excluir" cancel-text="Cancelar" :is-processing="isProcessingDelete" variant="danger"
            @close="closeDeleteModal" @confirm="confirmDelete" />

        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead class="w-[80px] text-center">Código</TableHead>
                    <TableHead>Título</TableHead>
                    <TableHead class="w-[100px] text-center">Status</TableHead>
                    <TableHead class="w-[100px] text-center">Visibilidade</TableHead>
                    <TableHead class="w-[120px] text-center">Autor</TableHead>
                    <TableHead class="w-[100px] text-center">Respostas</TableHead>
                    <TableHead class="w-[120px] text-center">Atualizado</TableHead>
                    <TableHead class="w-[240px] text-center">Ações</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-for="form in forms.data" :key="form.id" :class="{ 'opacity-50': isProcessing(form.id) }">
                    <TableCell class="font-medium text-center text-xs text-gray-500">
                        {{ form.code ? form.code.substring(0, 8) + "..." : `#${form.id}` }}
                    </TableCell>
                    <TableCell>
                        <div class="flex flex-col">
                            <span class="font-medium text-gray-900">{{ truncate(form.title, 40) }}</span>
                            <span class="text-xs text-gray-500 mt-0.5">{{ truncate(form.description, 60) }}</span>
                        </div>
                    </TableCell>
                    <TableCell class="text-center">
                        <span :class="[
                            'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                            formatStatus(form.status).class
                        ]">
                            {{ formatStatus(form.status).label }}
                        </span>
                    </TableCell>
                    <TableCell class="text-center">
                        <div class="flex items-center justify-center gap-1">
                            <Globe v-if="form.is_public" class="w-4 h-4 text-green-600" title="Público" />
                            <Lock v-else class="w-4 h-4 text-gray-400" title="Privado" />
                            <span class="text-xs text-gray-600">
                                {{ form.is_public ? "Público" : "Privado" }}
                            </span>
                        </div>
                    </TableCell>
                    <TableCell class="text-center">
                        <div v-if="form.created_by" class="flex items-center justify-center gap-1">
                            <User class="w-3 h-3 text-gray-400" />
                            <span class="text-sm text-gray-700">
                                {{ form.created_by }}
                            </span>
                        </div>
                        <span v-else class="text-sm text-gray-400">-</span>
                    </TableCell>
                    <TableCell class="text-center">
                        <div class="flex items-center justify-center gap-1">
                            <FileText class="w-3 h-3 text-gray-400" />
                            <span class="text-sm font-medium text-gray-700">
                                {{ form.responses_count || 0 }}
                            </span>
                        </div>
                    </TableCell>
                    <TableCell class="text-center">
                        <div class="flex items-center justify-center gap-1">
                            <Calendar class="w-3 h-3 text-gray-400" />
                            <span class="text-xs text-gray-600">
                                {{ form.updated_at }}
                            </span>
                        </div>
                    </TableCell>
                    <TableCell class="text-center">
                        <div class="flex justify-center items-center gap-1">
                            <!-- Visualizar Público -->
                            <button @click="viewFormPublic(form)"
                                :disabled="form.status !== 'ativo' || isProcessing(form.id)" :class="[
                                    'p-1.5 rounded-md transition-colors',
                                    form.status === 'ativo'
                                        ? 'text-blue-600 hover:bg-blue-50 hover:text-blue-900'
                                        : 'text-gray-300 cursor-not-allowed'
                                ]"
                                :title="form.status === 'ativo' ? 'Abrir página pública (nova aba)' : 'Formulário não está ativo'">
                                <ExternalLink v-if="form.status === 'ativo'" class="w-4 h-4" />
                                <Globe v-else class="w-4 h-4" />
                            </button>

                            <!-- Visualizar -->
                            <button @click="viewForm(form)" :disabled="isProcessing(form.id)"
                                class="p-1.5 rounded-md text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors disabled:opacity-50"
                                title="Visualizar">
                                <Eye class="w-4 h-4" />
                            </button>

                            <!-- Editar -->
                            <button v-if="can.edit" @click="editForm(form)" :disabled="isProcessing(form.id)"
                                class="p-1.5 rounded-md text-cyan-600 hover:bg-cyan-50 hover:text-cyan-900 transition-colors disabled:opacity-50"
                                title="Editar">
                                <Pencil class="w-4 h-4" />
                            </button>

                            <!-- TOGGLE VISIBILIDADE (PÚBLICO/PRIVADO) -->
                            <button v-if="can.toggleVisibility" @click="openVisibilityModal(form)"
                                :disabled="isProcessing(form.id)" :class="[
                                    'p-1.5 rounded-md transition-colors disabled:opacity-50',
                                    form.is_public
                                        ? 'text-green-600 hover:bg-green-50 hover:text-green-900'
                                        : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
                                ]" :title="form.is_public ? 'Tornar privado' : 'Tornar público'">
                                <EyeOff v-if="form.is_public" class="w-4 h-4" />
                                <Globe v-else class="w-4 h-4" />
                            </button>

                            <!-- ATIVAR (rascunho/pausado → ativo) -->
                            <button v-if="canActivate(form) && can.edit" @click="openActionModal(form, 'activate')"
                                :disabled="isProcessing(form.id)"
                                class="p-1.5 rounded-md text-green-600 hover:bg-green-50 hover:text-green-900 transition-colors disabled:opacity-50"
                                title="Ativar formulário">
                                <Play class="w-4 h-4" />
                            </button>

                            <!-- PAUSAR (ativo → pausado) -->
                            <button v-if="canPause(form) && can.edit" @click="openActionModal(form, 'pause')"
                                :disabled="isProcessing(form.id)"
                                class="p-1.5 rounded-md text-yellow-600 hover:bg-yellow-50 hover:text-yellow-900 transition-colors disabled:opacity-50"
                                title="Pausar formulário">
                                <Pause class="w-4 h-4" />
                            </button>

                            <!-- ENCERRAR (ativo/pausado → encerrado) -->
                            <button v-if="canClose(form) && can.edit" @click="openActionModal(form, 'close')"
                                :disabled="isProcessing(form.id)"
                                class="p-1.5 rounded-md text-orange-600 hover:bg-orange-50 hover:text-orange-900 transition-colors disabled:opacity-50"
                                title="Encerrar formulário">
                                <Square class="w-4 h-4" />
                            </button>

                            <!-- EXCLUIR -->
                            <button v-if="can.delete" @click="openDeleteModal(form)" :disabled="deletingId === form.id"
                                class="p-1.5 rounded-md text-red-600 hover:bg-red-50 hover:text-red-900 transition-colors disabled:opacity-50"
                                :title="deletingId === form.id ? 'Excluindo...' : 'Excluir'">
                                <Trash2 class="w-4 h-4" :class="{ 'animate-pulse': deletingId === form.id }" />
                            </button>
                        </div>
                    </TableCell>
                </TableRow>
                <TableRow v-if="!forms.data || forms.data.length === 0">
                    <TableCell colspan="8" class="text-center py-8 text-gray-500">
                        Nenhum formulário encontrado.
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>
