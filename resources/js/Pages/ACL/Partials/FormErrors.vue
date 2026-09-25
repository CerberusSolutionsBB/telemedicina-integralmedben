<script setup>
import { computed } from "vue";
import { AlertCircle } from "lucide-vue-next";

// Resumo dos erros de validação retornados pelo backend
const props = defineProps({
    errors: { type: Object, default: () => ({}) },
});

const messages = computed(() => [...new Set(Object.values(props.errors).filter(Boolean))]);
</script>

<template>
    <div v-if="messages.length" data-form-errors role="alert" aria-live="assertive"
        class="flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 p-4">
        <AlertCircle class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-600" />
        <div class="text-sm text-red-800">
            <p class="font-semibold">
                {{ messages.length === 1 ? "Corrija o erro abaixo para continuar:" : `Corrija os ${messages.length} erros abaixo para continuar:` }}
            </p>
            <ul class="mt-1 list-disc space-y-0.5 pl-5">
                <li v-for="message in messages" :key="message">{{ message }}</li>
            </ul>
        </div>
    </div>
</template>
