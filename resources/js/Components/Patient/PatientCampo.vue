<script setup>
/**
 * Par rótulo/valor da ficha do beneficiário. Sem valor, mostra "Não informado".
 */
defineProps({
    rotulo: { type: String, required: true },
    valor: { type: [String, Number], default: null },
});
</script>

<template>
    <div>
        <dt class="text-sm text-gray-500">{{ rotulo }}</dt>
        <dd class="mt-0.5 break-words text-base text-gray-900">
            <slot>
                <template v-if="valor !== null && valor !== ''">{{ valor }}</template>
                <span v-else class="text-gray-400">Não informado</span>
            </slot>
        </dd>
    </div>
</template>
