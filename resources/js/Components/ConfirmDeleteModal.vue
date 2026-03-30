<script setup>
import { computed } from 'vue';
import { Button } from '@/Components/ui/button';
import {
    AlertTriangle,
    X,
    Trash2,
    Loader2
} from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    title: {
        type: String,
        default: 'Confirmar Exclusão'
    },
    message: {
        type: String,
        default: 'Tem certeza que deseja excluir este item?'
    },
    itemName: {
        type: String,
        default: ''
    },
    warningMessage: {
        type: String,
        default: 'Esta ação não pode ser desfeita.'
    },
    confirmText: {
        type: String,
        default: 'Excluir'
    },
    cancelText: {
        type: String,
        default: 'Cancelar'
    },
    isProcessing: {
        type: Boolean,
        default: false
    },
    variant: {
        type: String,
        default: 'danger', // 'danger' | 'warning' | 'info'
        validator: (value) => ['danger', 'warning', 'info'].includes(value)
    }
});

const emit = defineEmits(['close', 'confirm']);

const variantConfig = computed(() => {
    const configs = {
        danger: {
            icon: Trash2,
            iconBg: 'bg-red-100',
            iconColor: 'text-red-600',
            buttonBg: 'bg-red-600 hover:bg-red-700',
            buttonHover: 'hover:bg-red-700',
            borderColor: 'border-red-200',
            bgColor: 'bg-red-50'
        },
        warning: {
            icon: AlertTriangle,
            iconBg: 'bg-yellow-100',
            iconColor: 'text-yellow-600',
            buttonBg: 'bg-yellow-600 hover:bg-yellow-700',
            buttonHover: 'hover:bg-yellow-700',
            borderColor: 'border-yellow-200',
            bgColor: 'bg-yellow-50'
        },
        info: {
            icon: AlertTriangle,
            iconBg: 'bg-blue-100',
            iconColor: 'text-blue-600',
            buttonBg: 'bg-blue-600 hover:bg-blue-700',
            buttonHover: 'hover:bg-blue-700',
            borderColor: 'border-blue-200',
            bgColor: 'bg-blue-50'
        }
    };
    return configs[props.variant];
});

const handleClose = () => {
    if (!props.isProcessing) {
        emit('close');
    }
};

const handleConfirm = () => {
    if (!props.isProcessing) {
        emit('confirm');
    }
};
</script>

<template>
    <Teleport to="body">
        <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="handleClose">
                </div>

                <!-- Modal -->
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all"
                    :class="{ 'scale-95': isProcessing }">

                    <!-- Header com ícone -->
                    <div class="p-6 pb-0">
                        <div class="flex items-start gap-4">
                            <div
                                :class="['w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0', variantConfig.iconBg]">
                                <component :is="variantConfig.icon" :class="['w-6 h-6', variantConfig.iconColor]" />
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    {{ title }}
                                </h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    {{ message }}
                                </p>
                            </div>
                            <button @click="handleClose" :disabled="isProcessing"
                                class="p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                                <X class="w-5 h-5" />
                            </button>
                        </div>
                    </div>

                    <!-- Item destacado -->
                    <div v-if="itemName" class="px-6 py-4">
                        <div :class="['p-3 rounded-lg border', variantConfig.bgColor, variantConfig.borderColor]">
                            <p class="text-sm font-medium text-gray-900 break-words">
                                "{{ itemName }}"
                            </p>
                        </div>
                    </div>

                    <!-- Warning -->
                    <div class="px-6 pb-6">
                        <div class="flex items-start gap-2 p-3 bg-gray-50 rounded-lg border border-gray-200">
                            <AlertTriangle class="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" />
                            <p class="text-sm text-gray-600">
                                {{ warningMessage }}
                            </p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
                        <Button variant="outline" class="flex-1" @click="handleClose" :disabled="isProcessing">
                            {{ cancelText }}
                        </Button>
                        <Button :class="['flex-1 text-white gap-2', variantConfig.buttonBg]" @click="handleConfirm"
                            :disabled="isProcessing">
                            <Loader2 v-if="isProcessing" class="w-4 h-4 animate-spin" />
                            <component :is="variantConfig.icon" v-else class="w-4 h-4" />
                            <span v-if="isProcessing">Processando...</span>
                            <span v-else>{{ confirmText }}</span>
                        </Button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
