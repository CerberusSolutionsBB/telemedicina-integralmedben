<script setup>
import { AlertTriangle } from 'lucide-vue-next'

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Confirmar ação',
    },
    message: {
        type: String,
        default: '',
    },
    confirmText: {
        type: String,
        default: 'Confirmar',
    },
    cancelText: {
        type: String,
        default: 'Cancelar',
    },
    loading: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits([
    'confirm',
    'cancel',
])
</script>

<template>
    <Teleport to="body">
        <dialog v-if="props.open" open class="modal modal-open">
            <div class="modal-box max-w-md rounded-2xl shadow-2xl">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-red-100">
                        <AlertTriangle class="h-6 w-6 text-red-600" />
                    </div>

                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-900">
                            {{ props.title }}
                        </h3>

                        <p class="mt-2 text-sm leading-relaxed text-gray-500">
                            {{ props.message }}
                        </p>
                    </div>
                </div>

                <div class="modal-action">
                    <button type="button" class="btn btn-ghost" :disabled="props.loading" @click="emit('cancel')">
                        {{ props.cancelText }}
                    </button>

                    <button type="button" class="btn btn-error" :disabled="props.loading" @click="emit('confirm')">
                        <span v-if="props.loading" class="loading loading-spinner loading-sm" />

                        {{ props.confirmText }}
                    </button>
                </div>
            </div>

            <form method="dialog" class="modal-backdrop" @click.prevent="emit('cancel')">
                <button type="button">close</button>
            </form>
        </dialog>
    </Teleport>
</template>
