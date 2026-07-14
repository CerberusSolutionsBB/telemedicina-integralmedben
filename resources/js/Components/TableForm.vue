<script setup>
import { ref, computed } from "vue";
import {
    Pencil, Trash2, Eye, Globe, Lock, FileText, Calendar, User, Play,
    ExternalLink, Pause, Square, EyeOff
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
import { showToast } from '@/Utils/toast';

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
    "change-status",
    "delete-form",
    "toggle-visibility"
]);

// ==========================================
// ESTADOS DOS MODAIS
// ==========================================
const deleteModal = ref({
    show: false,
    form: null,
    isProcessing: false
});

const visibilityModal = ref({
    show: false,
    form: null,
    isProcessing: false,
});

// ==========================================
// CONFIGURAÇÕES DE STATUS
// ==========================================
const STATUS_CONFIG = {
    rascunho: {
        label: "Rascunho",
        class: "bg-gray-100 text-gray-800 border-gray-200",
        icon: FileText
    },
    ativo: {
        label: "Ativo",
        class: "bg-green-100 text-green-800 border-green-200",
        icon: Play
    },
    pausado: {
        label: "Pausado",
        class: "bg-yellow-100 text-yellow-800 border-yellow-200",
        icon: Pause
    },
    encerrado: {
        label: "Encerrado",
        class: "bg-red-100 text-red-800 border-red-200",
        icon: Square
    },
};

// Workflow de transições permitidas
const STATUS_WORKFLOW = {
    rascunho: ['ativo'],
    ativo: ['pausado', 'encerrado'],
    pausado: ['ativo', 'encerrado'],
    encerrado: []
};

// Configuração das ações de status
const STATUS_ACTIONS = {
    ativo: {
        label: 'Ativar',
        icon: Play,
        color: 'green',
        confirm: false
    },
    pausado: {
        label: 'Pausar',
        icon: Pause,
        color: 'yellow',
        confirm: false
    },
    encerrado: {
        label: 'Encerrar',
        icon: Square,
        color: 'orange',
        confirm: true,
        confirmMessage: (form) => `⚠️ Tem certeza que deseja ENCERRAR o formulário "${form.title}"?\n\nEsta ação não pode ser desfeita!`
    }
};

// ==========================================
// COMPUTED
// ==========================================
const visibilityModalConfig = computed(() => {
    const form = visibilityModal.value.form;
    if (!form) return {};

    const isPublic = form.is_public;
    return {
        title: isPublic ? 'Tornar Privado' : 'Tornar Público',
        description: isPublic
            ? `Deseja tornar o formulário "${form.title}" privado?\n\nEle não será mais acessível publicamente.`
            : `Deseja tornar o formulário "${form.title}" público?\n\nEle poderá ser acessido por qualquer pessoa com o link.`,
        confirmText: isPublic ? 'Sim, Tornar Privado' : 'Sim, Tornar Público',
        confirmVariant: isPublic ? 'warning' : 'success',
        icon: isPublic ? 'lock' : 'globe',
        processingText: 'Alterando...',
    };
});

// ==========================================
// MÉTODOS
// ==========================================

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

/**
 * Executa mudança de status com confirmação se necessário
 */
const executeStatusChange = (form, nextStatus) => {
    const action = STATUS_ACTIONS[nextStatus];

    // Confirmação para ações críticas
    if (action.confirm) {
        const message = typeof action.confirmMessage === 'function'
            ? action.confirmMessage(form)
            : action.confirmMessage;

        if (!confirm(message)) {
            return;
        }
    }

    // Emite evento padronizado para o pai
    emit('change-status', {
        form: form,
        action: nextStatus,
        fromStatus: form.status,
        toStatus: nextStatus,
        actionLabel: action.label,
        requiresConfirmation: action.confirm
    });
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
    setTimeout(() => {
        visibilityModal.value.form = null;
        visibilityModal.value.isProcessing = false;
    }, 200);
};

const confirmToggleVisibility = () => {
    const form = visibilityModal.value.form;
    if (!form) return;

    visibilityModal.value.isProcessing = true;

    router.post(
        route('forms.toggle-visibility'),
        {
            form_id: form.id,
            is_public: !form.is_public
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                const message = form.is_public
                    ? 'Formulário agora está privado.'
                    : 'Formulário agora está público.';
                showToast(message, 'success');
                form.is_public = !form.is_public;
                closeVisibilityModal();
            },
            onError: (errors) => {
                console.error('Erro ao alternar visibilidade:', errors);
                const errorMsg = Object.values(errors).join('\n');
                showToast('Erro: ' + errorMsg, 'error');
                visibilityModal.value.isProcessing = false;
            },
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
    setTimeout(() => {
        deleteModal.value.form = null;
        deleteModal.value.isProcessing = false;
    }, 200);
};

const confirmDelete = () => {
    const form = deleteModal.value.form;
    if (!form) return;

    deleteModal.value.isProcessing = true;

    router.delete(route('forms.destroy', form.id), {
        preserveScroll: true,
        onSuccess: () => {
            showToast('Formulário excluído com sucesso!', 'success');
            closeDeleteModal();
        },
        onError: (errors) => {
            console.error('Erro ao excluir formulário:', errors);
            const errorMsg = Object.values(errors).join('\n');
            showToast('Erro: ' + errorMsg, 'error');
            deleteModal.value.isProcessing = false;
        },
    });
};

// ==========================================
// HELPERS
// ==========================================

/**
 * Verifica se formulário está em processamento
 */
const isProcessing = (formId) => {
    return props.activatingId === formId ||
        props.deletingId === formId ||
        props.togglingVisibilityId === formId;
};

/**
 * Retorna ações disponíveis para o status atual
 */
const getAvailableStatusActions = (form) => {
    if (!can.edit) return [];
    return STATUS_WORKFLOW[form.status] || [];
};

/**
 * Verifica permissões específicas
 */
const canActivate = (form) => STATUS_WORKFLOW[form.status]?.includes('ativo');
const canPause = (form) => STATUS_WORKFLOW[form.status]?.includes('pausado');
const canClose = (form) => STATUS_WORKFLOW[form.status]?.includes('encerrado');

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
</script>

<template>
    <div>
        <!-- Modal de Visibilidade -->
        <ConfirmActionModal :show="visibilityModal.show" :title="visibilityModalConfig.title"
            :description="visibilityModalConfig.description" :confirm-text="visibilityModalConfig.confirmText"
            cancel-text="Cancelar" :confirm-variant="visibilityModalConfig.confirmVariant"
            :icon="visibilityModalConfig.icon" :is-processing="visibilityModal.isProcessing"
            :processing-text="visibilityModalConfig.processingText" @close="closeVisibilityModal"
            @confirm="confirmToggleVisibility" />

        <!-- Modal de Delete -->
        <ConfirmDeleteModal :show="deleteModal.show" :item-name="deleteModal.form?.title" title="Excluir Formulário"
            message="Tem certeza que deseja excluir este formulário?"
            warning-message="Esta ação não pode ser desfeita e todas as respostas associadas serão perdidas."
            confirm-text="Sim, Excluir" cancel-text="Cancelar" :is-processing="deleteModal.isProcessing"
            processing-text="Excluindo..." variant="danger" @close="closeDeleteModal" @confirm="confirmDelete" />

        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead class="w-[80px] text-center">Código</TableHead>
                    <TableHead>Título</TableHead>
                    <TableHead class="w-[100px] text-center">Status</TableHead>
                    <TableHead class="w-[100px] text-center">Visibilidade</TableHead>
                    <TableHead class="w-[120px] text-center">Autor</TableHead>
                    <TableHead class="w-[100px] text-center">Respostas</TableHead>
                    <TableHead class="w-[240px] text-center">Ações</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-for="form in forms.data" :key="form.id"
                    :class="{ 'opacity-50 pointer-events-none': isProcessing(form.id) }">
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
                            'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold border',
                            STATUS_CONFIG[form.status]?.class
                        ]">
                            <component :is="STATUS_CONFIG[form.status]?.icon" class="w-3 h-3" />
                            {{ STATUS_CONFIG[form.status]?.label }}
                        </span>
                    </TableCell>

                    <TableCell class="text-center">
                        <div class="flex items-center justify-center gap-1.5">
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
                            <span class="text-sm text-gray-700">{{ form.created_by }}</span>
                        </div>
                        <span v-else class="text-sm text-gray-400">-</span>
                    </TableCell>

                    <TableCell class="text-center">
                        <div class="flex items-center justify-center gap-1">
                            <FileText class="w-3 h-3 text-gray-400" />
                            <span class="text-sm font-medium text-gray-700">{{ form.responses_count || 0 }}</span>
                        </div>
                    </TableCell>


                    <TableCell class="text-center">
                        <div class="flex justify-center items-center gap-1">
                            <!-- Visualizar Público -->


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

                            <!-- Toggle Visibilidade -->
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

                            <!-- Ações de Status Dinâmicas -->
                            <button v-if="canActivate(form) && can.edit" @click="executeStatusChange(form, 'ativo')"
                                :disabled="isProcessing(form.id)"
                                class="p-1.5 rounded-md text-green-600 hover:bg-green-50 hover:text-green-900 transition-colors disabled:opacity-50"
                                title="Ativar formulário">
                                <Play class="w-4 h-4" />
                            </button>

                            <button v-if="canPause(form) && can.edit" @click="executeStatusChange(form, 'pausado')"
                                :disabled="isProcessing(form.id)"
                                class="p-1.5 rounded-md text-yellow-600 hover:bg-yellow-50 hover:text-yellow-900 transition-colors disabled:opacity-50"
                                title="Pausar formulário">
                                <Pause class="w-4 h-4" />
                            </button>

                            <button v-if="canClose(form) && can.edit" @click="executeStatusChange(form, 'encerrado')"
                                :disabled="isProcessing(form.id)"
                                class="p-1.5 rounded-md text-orange-600 hover:bg-orange-50 hover:text-orange-900 transition-colors disabled:opacity-50"
                                title="Encerrar formulário">
                                <Square class="w-4 h-4" />
                            </button>

                            <!-- Excluir -->
                            <button v-if="can.delete" @click="openDeleteModal(form)" :disabled="isProcessing(form.id)"
                                class="p-1.5 rounded-md text-red-600 hover:bg-red-50 hover:text-red-900 transition-colors disabled:opacity-50"
                                title="Excluir">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </TableCell>
                </TableRow>

                <TableRow v-if="!forms.data || forms.data.length === 0">
                    <TableCell colspan="8" class="text-center py-8 text-gray-500">
                        <div class="flex flex-col items-center gap-2">
                            <FileText class="w-8 h-8 text-gray-300" />
                            <span>Nenhum formulário encontrado.</span>
                        </div>
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>
