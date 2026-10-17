<script setup>
/**
 * Título de seção do formulário com número da ordem de preenchimento (1, 2, 3...)
 * e uma frase curta dizendo o que fazer ali.
 */
defineProps({
    numero: { type: Number, default: null },
    titulo: { type: String, required: true },
    ajuda: { type: String, default: "" },
    id: { type: String, default: null },
});
</script>

<template>
    <div class="flex items-start gap-3">
        <span v-if="numero" aria-hidden="true"
            class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-cyan-600 text-sm font-bold text-white">{{ numero }}</span>
        <div class="min-w-0">
            <h2 :id="id" class="text-base font-semibold text-gray-900">
                <span v-if="numero" class="sr-only">Passo {{ numero }}: </span>{{ titulo }}
            </h2>
            <p v-if="ajuda" class="mt-0.5 text-sm text-gray-600">{{ ajuda }}</p>
        </div>
    </div>
</template>
