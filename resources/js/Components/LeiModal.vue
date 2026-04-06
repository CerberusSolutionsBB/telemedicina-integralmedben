<script setup>
import { computed } from 'vue';
import { X, FileText, Calendar, Tag, CheckCircle } from 'lucide-vue-next';

const props = defineProps({
    lei: {
        type: Object,
        default: null
    },
    show: {
        type: Boolean,
        default: false
    },
    // ⭐ NOVO: Receber a cor primária do formulário
    primaryColor: {
        type: String,
        default: '#06b6d4' // Default ciano (Integral Medben)
    },
    secondaryColor: {
        type: String,
        default: '#0891b2'
    }
});

const emit = defineEmits(['close']);

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });
};

const typeLabel = computed(() => {
    const types = {
        lgpd: 'LGPD',
        gdpr: 'GDPR',
        ccpa: 'CCPA',
    };
    return types[props.lei?.type] || props.lei?.type || '—';
});

// ⭐ Computed para o gradiente do header
const headerGradient = computed(() => {
    return `linear-gradient(135deg, ${props.primaryColor} 0%, ${props.secondaryColor} 100%)`;
});
</script>

<template>
    <Teleport to="body">
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="show && lei" class="fixed inset-0 z-50 flex items-center justify-center p-4"
                @click.self="emit('close')">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="emit('close')" />

                <!-- Modal -->
                <Transition enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-2">
                    <div v-if="show"
                        class="relative z-10 bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[85vh] flex flex-col overflow-hidden">

                        <!-- ⭐ HEADER COM COR PRIMÁRIA -->
                        <div class="flex items-start justify-between p-6 border-b border-gray-100"
                            :style="{ background: headerGradient }">
                            <div class="flex items-start gap-3">
                                <div
                                    class="flex-shrink-0 w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                                    <FileText class="w-5 h-5 text-white" />
                                </div>
                                <div>
                                    <h2 class="text-xl font-bold text-white leading-tight">
                                        {{ lei.title }}
                                    </h2>
                                    <div class="flex items-center gap-2 mt-1">
                                        <!-- Badge tipo -->
                                        <span
                                            class="inline-flex items-center gap-1 px-2 py-0.5 bg-white/20 rounded-full text-xs font-medium text-white">
                                            <Tag class="w-3 h-3" />
                                            {{ typeLabel }}
                                        </span>
                                        <!-- Badge status -->
                                        <span v-if="lei.status === 'ativo'"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 bg-green-400/30 rounded-full text-xs font-medium text-white">
                                            <CheckCircle class="w-3 h-3" />
                                            Ativo
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Botão fechar -->
                            <button @click="emit('close')"
                                class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-lg bg-white/20 hover:bg-white/30 text-white transition-colors duration-150">
                                <X class="w-4 h-4" />
                            </button>
                        </div>

                        <!-- Meta info -->
                        <div
                            class="flex items-center gap-6 px-6 py-3 bg-gray-50 border-b border-gray-100 text-xs text-gray-500">
                            <span class="flex items-center gap-1.5">
                                <Calendar class="w-3.5 h-3.5" />
                                Criado em {{ formatDate(lei.created_at) }}
                            </span>
                            <span v-if="lei.updated_at !== lei.created_at" class="flex items-center gap-1.5">
                                <Calendar class="w-3.5 h-3.5" />
                                Atualizado em {{ formatDate(lei.updated_at) }}
                            </span>
                        </div>

                        <!-- Conteúdo -->
                        <div class="flex-1 overflow-y-auto p-6">
                            <div class="prose prose-sm max-w-none text-gray-700 leading-relaxed" v-html="lei.text" />
                        </div>

                        <!-- ⭐ FOOTER COM BOTÃO NA COR PRIMÁRIA -->
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                            <button @click="emit('close')"
                                class="px-5 py-2 rounded-lg text-sm font-medium text-white transition-all duration-150 hover:opacity-90 hover:scale-105"
                                :style="{ backgroundColor: primaryColor }">
                                Fechar
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
