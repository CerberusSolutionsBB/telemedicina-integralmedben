<script setup>
import { computed } from 'vue';

// Barras por mês; a barra `destaque` (índice do mês atual) fica em vermelho.
const props = defineProps({
  valores: { type: Array, default: () => [] },
  labels: { type: Array, default: () => [] },
  destaque: { type: Number, default: -1 },
});

const max = computed(() => Math.max(...props.valores, 1));
</script>

<template>
  <div>
    <div class="flex h-44 items-end gap-2 border-b border-gray-200 sm:gap-4">
      <div v-for="(v, i) in valores" :key="i" class="flex h-full flex-1 flex-col items-center justify-end" :title="`${labels[i]}: ${v}`">
        <span v-if="v > 0" class="mb-1 text-xs font-bold text-gray-900">{{ v }}</span>
        <div class="w-full max-w-[1.5rem] rounded-t-md"
          :class="i === destaque ? 'bg-red-600' : 'bg-[#22b8cf]'"
          :style="{ height: v > 0 ? (v / max * 85) + '%' : '2px', opacity: v > 0 ? 1 : 0.25 }" />
      </div>
    </div>
    <div class="mt-2 flex gap-2 sm:gap-4">
      <span v-for="l in labels" :key="l" class="flex-1 text-center text-xs text-gray-500">{{ l }}</span>
    </div>
  </div>
</template>
