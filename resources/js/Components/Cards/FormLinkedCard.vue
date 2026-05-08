<script setup>
import { ref, watch, computed } from 'vue'
import { Clock, Edit, Check, X, AlertTriangle, CalendarOff } from 'lucide-vue-next'
import DateTimeInput from '@/Components/DateTimeInput.vue'
const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
})
const emit = defineEmits(['update:expiresAt'])
const isEditing = ref(false)
const expiresAt = ref(props.item.expires_at || '')
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
const timeLeft = computed(() => {
    if (!props.item.expires_at || isExpired.value) return null
    const diff = new Date(props.item.expires_at) - new Date()
    const days = Math.floor(diff / (1000 * 60 * 60 * 24))
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
    if (days > 0) return `${days}d ${hours}h`
    if (hours > 0) return `${hours}h`
    return '< 1h'
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
const startEditing = () => {
    expiresAt.value = props.item.expires_at || ''
    isEditing.value = true
}
const cancelEditing = () => {
    isEditing.value = false
}
const confirmEditing = () => {
    emit('update:expiresAt', {
        id: props.item.id,
        expires_at: expiresAt.value || null,
    })
    isEditing.value = false
}
</script>
<template>
    <div
        :class="[
            'group border rounded-xl p-5 transition-all duration-200',
            isExpired
                ? 'border-red-200 bg-red-50/50 hover:bg-red-50 hover:shadow-sm hover:border-red-300'
                : expiresSoon
                  ? 'border-amber-200 bg-amber-50/50 hover:bg-amber-50 hover:shadow-sm hover:border-amber-300'
                  : 'border-gray-200 bg-white hover:bg-gray-50/50 hover:shadow-sm hover:border-gray-300',
        ]"
    >
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-5">
             <div class="min-w-0 flex-1">
                <div class="  items-start gap-2.5">
                    <span
                        v-if="item.form?.code"
                        class="inline-flex shrink-0 items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700 tracking-wide uppercase"
                    >
                        {{ item.form.code }}
                    </span>
                    <h3 class="font-semibold text-gray-900 leading-snug">
                        {{ item.form?.title || 'Sem título' }}
                    </h3>
                </div>
                <p class="text-sm text-gray-500 mt-2 leading-relaxed line-clamp-2">
                    {{ item.form?.description || 'Sem descrição' }}
                </p>
                <div class="flex flex-wrap items-center gap-2 mt-4">
                    <!-- <span
                        :class="[
                            'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border',
                            item.status === 'ativo'
                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                : 'bg-amber-50 text-amber-700 border-amber-200',
                        ]"
                    >
                        <span
                            :class="[
                                'w-1.5 h-1.5 rounded-full',
                                item.status === 'ativo' ? 'bg-emerald-500' : 'bg-amber-500',
                            ]"
                        />
                        {{ item.status }}
                    </span> -->
                    <span
                        v-if="isExpired"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200"
                    >
                        <AlertTriangle class="w-3.5 h-3.5" />
                        Expirado
                    </span>
                    <span
                        v-else-if="expiresSoon"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200 animate-pulse"
                    >
                        <AlertTriangle class="w-3.5 h-3.5" />
                        Expira em {{ timeLeft }}
                    </span>
                    <span
                        v-else-if="item.expires_at"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200"
                    >
                        <Clock class="w-3.5 h-3.5" />
                        Ativo
                    </span>
                </div>
            </div>
            <!-- Coluna Direita: Data e Ações -->
            <div class="w-full md:w-72 shrink-0 space-y-3">
                <!-- Card de Expiração -->
                <div
                    :class="[
                        'flex items-center justify-between rounded-lg px-3 py-2.5 text-sm',
                        isExpired
                            ? 'bg-red-100/50 text-red-800'
                            : expiresSoon
                              ? 'bg-amber-100/50 text-amber-800'
                              : item.expires_at
                                ? 'bg-gray-100 text-gray-700'
                                : 'bg-gray-50 text-gray-400',
                    ]"
                >
                    <div class="flex items-center gap-2">
                        <component
                            :is="item.expires_at ? Clock : CalendarOff"
                            class="w-4 h-4 shrink-0"
                        />
                        <span class="font-medium">
                            <template v-if="item.expires_at">
                                {{ isExpired ? 'Expirou em' : 'Expira em' }}
                            </template>
                            <template v-else>
                                Sem prazo
                            </template>
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-semibold tabular-nums">
                            {{ item.expires_at ? formatDateTime(item.expires_at) : '—' }}
                        </span>
                        <button
                            v-if="!isEditing"
                            type="button"
                            class="opacity-0 group-hover:opacity-100 transition-all p-1.5 rounded-md hover:bg-blue-100 text-gray-400 hover:text-blue-600"
                            title="Alterar data"
                            @click="startEditing"
                        >
                            <Edit class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>
                <!-- Editor Inline -->
                <div
                    v-if="isEditing"
                    class="rounded-xl border-2 border-blue-200 bg-white p-4 space-y-4 shadow-lg"
                >
                    <DateTimeInput
                        :id="`expires_at_${item.id}`"
                        label="Nova data de expiração"
                        v-model="expiresAt"
                    />
                    <div class="flex justify-end gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors"
                            @click="cancelEditing"
                        >
                            <X class="w-4 h-4 text-gray-500" />
                            Cancelar
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium bg-emerald-600 text-white hover:bg-emerald-700 transition-colors shadow-sm"
                            @click="confirmEditing"
                        >
                            <Check class="w-4 h-4" />
                            Salvar
                        </button>
                    </div>
                </div>
                <!-- Data de Vinculação -->
                <div class="flex items-center justify-between text-xs text-gray-400 px-1">
                    <span>Vinculado em</span>
                    <span class="tabular-nums">{{ formatDateTime(item.created_at) }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
