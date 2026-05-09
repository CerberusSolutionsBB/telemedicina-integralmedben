<script setup>
import { onMounted, onUnmounted } from 'vue'
import { ref, watch, computed, nextTick } from 'vue'
import {
    Clock,
    Edit,
    Check,
    X,
    AlertTriangle,
    CalendarOff,
    Trash2,
    Unlink,
    Loader2,
    CalendarDays,
} from 'lucide-vue-next'
import DateTimeInput from '@/Components/DateTimeInput.vue'
const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
    loading: {
        type: Boolean,
        default: false,
    },
})
const emit = defineEmits([
    'update:expiresAt',
    'remove:link',
])
const isEditing = ref(false)
const isConfirmingDelete = ref(false)
const expiresAt = ref(props.item.expires_at || '')
const dateInputRef = ref(null)
watch(
    () => props.item.expires_at,
    (value) => {
        expiresAt.value = value || ''
    },
)
const isExpired = computed(() => {
    if (!props.item.expires_at) return false
    return new Date(props.item.expires_at) < new Date()
})
const expiresSoon = computed(() => {
    if (!props.item.expires_at || isExpired.value) return false
    const diff = new Date(props.item.expires_at) - new Date()
    return diff < 1000 * 60 * 60 * 24 * 3
})
const isValidDate = computed(() => {
    if (!expiresAt.value) return true
    const date = new Date(expiresAt.value)
    return !Number.isNaN(date.getTime()) && date > new Date()
})
const timeLeft = computed(() => {
    if (!props.item.expires_at || isExpired.value) return null
    const diff = new Date(props.item.expires_at) - new Date()
    const days = Math.floor(diff / (1000 * 60 * 60 * 24))
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))
    if (days > 0) return `${days}d ${hours}h`
    if (hours > 0) return `${hours}h ${minutes}min`
    return `${minutes}min`
})
const statusConfig = computed(() => {
    if (isExpired.value) {
        return {
            label: 'Expirado',
            variant: 'error',
            icon: AlertTriangle,
            color: 'text-red-700 bg-red-100 border-red-200',
            bgColor: 'bg-red-50/80',
            borderColor: 'border-red-200',
        }
    }
    if (expiresSoon.value) {
        return {
            label: `Expira em ${timeLeft.value}`,
            variant: 'warning',
            icon: Clock,
            color: 'text-amber-700 bg-amber-100 border-amber-200',
            bgColor: 'bg-amber-50/80',
            borderColor: 'border-amber-200',
        }
    }
    if (props.item.expires_at) {
        return {
            label: 'Ativo',
            variant: 'success',
            icon: Check,
            color: 'text-emerald-700 bg-emerald-100 border-emerald-200',
            bgColor: 'bg-white',
            borderColor: 'border-gray-200',
        }
    }
    return {
        label: 'Sem prazo',
        variant: 'neutral',
        icon: CalendarDays,
        color: 'text-gray-600 bg-gray-100 border-gray-200',
        bgColor: 'bg-white',
        borderColor: 'border-gray-200',
    }
})
const formatDateTime = (date) => {
    if (!date) return '—'
    const parsedDate = new Date(date)
    if (Number.isNaN(parsedDate.getTime())) return '—'
    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(parsedDate)
}
const startEditing = async () => {
    expiresAt.value = props.item.expires_at || ''
    isEditing.value = true
    await nextTick()
    dateInputRef.value?.$el?.querySelector('input')?.focus()
}
const cancelEditing = () => {
    expiresAt.value = props.item.expires_at || ''
    isEditing.value = false
    isConfirmingDelete.value = false
}
const confirmEditing = () => {
    if (!isValidDate.value) return
    emit('update:expiresAt', {
        id: props.item.id,
        expires_at: expiresAt.value || null,
    })
    isEditing.value = false
}
const removeExpiresAt = () => {
    if (!isConfirmingDelete.value) {
        isConfirmingDelete.value = true
        return
    }
    emit('update:expiresAt', {
        id: props.item.id,
        expires_at: null,
    })
    isEditing.value = false
    isConfirmingDelete.value = false
}
const removeLink = () => {

    emit('remove:link', props.item)

}
const handleKeydown = (e) => {
    if (e.key === 'Escape' && isEditing.value) {
        cancelEditing()
    }
}
onMounted(() => {
    document.addEventListener('keydown', handleKeydown)
})
onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown)
})
</script>
<template>
    <div :class="[
        'group relative rounded-2xl border p-5 shadow-sm transition-all duration-300',
        statusConfig.bgColor,
        statusConfig.borderColor,
        'hover:shadow-md hover:-translate-y-0.5',
    ]">
        <!-- Loading Overlay -->
        <div v-if="loading"
            class="absolute inset-0 z-10 flex items-center justify-center rounded-2xl bg-white/60 backdrop-blur-sm">
            <Loader2 class="w-8 h-8 animate-spin text-blue-600" />
        </div>
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <!-- Coluna Esquerda: Informações -->
            <div class="min-w-0 flex-1 space-y-3">
                <!-- Badges -->
                <div class="flex flex-wrap items-center gap-2">
                    <span v-if="item.form?.code"
                        class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">
                        {{ item.form.code }}
                    </span>
                    <span :class="[
                        'inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-medium',
                        statusConfig.color,
                    ]">
                        <component :is="statusConfig.icon" class="w-3.5 h-3.5" />
                        {{ statusConfig.label }}
                    </span>
                </div>
                <!-- Título & Descrição -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 leading-tight">
                        {{ item.form?.title || 'Sem título' }}
                    </h3>
                    <p class="mt-1.5 text-sm text-gray-500 leading-relaxed line-clamp-3">
                        {{ item.form?.description || 'Sem descrição cadastrada.' }}
                    </p>
                </div>
                <!-- Meta info mobile -->
                <div class="flex items-center gap-4 text-xs text-gray-400 lg:hidden">
                    <span class="flex items-center gap-1">
                        <CalendarDays class="w-3.5 h-3.5" />
                        Vinculado {{ formatDateTime(item.created_at) }}
                    </span>
                </div>
            </div>
            <!-- Coluna Direita: Ações -->
            <div class="w-full lg:w-80 space-y-3">
                <!-- Card de Expiração -->
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-medium uppercase tracking-wider text-gray-400">
                                Expiração
                            </p>
                            <p class="mt-1.5 text-sm font-semibold"
                                :class="isExpired ? 'text-red-600' : 'text-gray-800'">
                                <template v-if="item.expires_at">
                                    {{ formatDateTime(item.expires_at) }}
                                    <span v-if="timeLeft && !isExpired"
                                        class="block text-xs font-normal text-gray-500 mt-0.5">
                                        {{ timeLeft }} restantes
                                    </span>
                                </template>
                                <template v-else>
                                    <span class="text-gray-400 font-normal">Sem prazo definido</span>
                                </template>
                            </p>
                        </div>
                        <button v-if="!isEditing" type="button"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm transition-all hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            @click="startEditing">
                            <Edit class="w-3.5 h-3.5" />
                            {{ item.expires_at ? 'Alterar' : 'Definir' }}
                        </button>
                    </div>
                </div>
                <!-- Editor de Data (expandível) -->
                <Transition enter-active-class="transition-all duration-200 ease-out"
                    enter-from-class="opacity-0 -translate-y-2 scale-[0.98]"
                    enter-to-class="opacity-100 translate-y-0 scale-100"
                    leave-active-class="transition-all duration-150 ease-in"
                    leave-from-class="opacity-100 translate-y-0 scale-100"
                    leave-to-class="opacity-0 -translate-y-2 scale-[0.98]">
                    <div v-if="isEditing"
                        class="rounded-xl border-2 border-blue-200 bg-blue-50/80 p-4 shadow-lg backdrop-blur-sm">
                        <div class="flex items-center gap-2 mb-3">
                            <Clock class="w-4 h-4 text-blue-600" />
                            <span class="text-sm font-semibold text-blue-900">
                                Editar data de expiração
                            </span>
                        </div>
                        <DateTimeInput ref="dateInputRef" :id="`expires_at_${item.id}`" label="Nova data de expiração"
                            v-model="expiresAt" :error="!isValidDate && expiresAt ? 'Data deve ser futura' : null"
                            class="w-full" />
                        <!-- Ações do Editor -->
                        <div class="mt-4 space-y-2">
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" :disabled="!isValidDate && !!expiresAt" :class="[
                                    'inline-flex items-center justify-center gap-1.5 rounded-lg px-4 py-2 text-sm font-medium transition-all',
                                    (!isValidDate && !!expiresAt)
                                        ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                                        : 'bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm hover:shadow active:scale-[0.98]'
                                ]" @click="confirmEditing">
                                    <Check class="w-4 h-4" />
                                    Salvar
                                </button>
                                <button type="button"
                                    class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-all hover:bg-gray-50 active:scale-[0.98]"
                                    @click="cancelEditing">
                                    <X class="w-4 h-4" />
                                    Cancelar
                                </button>
                            </div>
                            <!-- Remover prazo com confirmação em 2 etapas -->
                            <button v-if="item.expires_at" type="button" :class="[
                                'inline-flex w-full items-center justify-center gap-1.5 rounded-lg px-4 py-2 text-sm font-medium transition-all',
                                isConfirmingDelete
                                    ? 'bg-red-600 text-white hover:bg-red-700 shadow-sm'
                                    : 'border border-red-200 bg-white text-red-600 hover:bg-red-50'
                            ]" @click="removeExpiresAt">
                                <Trash2 class="w-4 h-4" />
                                {{ isConfirmingDelete ? 'Clique novamente para confirmar' : 'Remover prazo' }}
                            </button>
                        </div>
                    </div>
                </Transition>
                <!-- Ações Destrutivas -->
                <div class="pt-2 border-t border-gray-100">
                    <button type="button"
                        class="group/btn inline-flex w-full items-center justify-center gap-2 rounded-lg border border-red-200 bg-white px-4 py-2.5 text-sm font-medium text-red-600 transition-all hover:bg-red-50 hover:border-red-300 hover:shadow-sm active:scale-[0.99]"
                        @click="removeLink">
                        <Unlink class="w-4 h-4 transition-transform group-hover/btn:rotate-12" />
                        Remover vínculo
                    </button>
                </div>
                <!-- Meta info desktop -->
                <div class="hidden lg:flex items-center justify-between text-xs text-gray-400 px-1">
                    <span class="flex items-center gap-1">
                        <CalendarDays class="w-3.5 h-3.5" />
                        Vinculado em
                    </span>
                    <span class="font-medium text-gray-500">{{ formatDateTime(item.created_at) }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
