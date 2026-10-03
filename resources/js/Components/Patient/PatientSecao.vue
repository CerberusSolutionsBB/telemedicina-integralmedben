<script setup>
/**
 * Seção da ficha do beneficiário: título com ícone, contagem opcional e conteúdo.
 */
defineProps({
    titulo: { type: String, required: true },
    icone: { type: [Object, Function], default: null },
    contagem: { type: Number, default: null },
});
</script>

<template>
    <section class="w-full rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
        <h2 class="mb-4 flex items-center gap-2 text-base font-semibold text-gray-900">
            <component :is="icone" v-if="icone" class="h-5 w-5 text-cyan-600" aria-hidden="true" />
            {{ titulo }}
            <span v-if="contagem !== null" class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">
                {{ contagem }}
            </span>
        </h2>
        <slot />
    </section>
</template>
