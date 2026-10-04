<script setup>
import { computed } from 'vue';

// Rosca com total no centro e legenda (valor e % de cada item).
const props = defineProps({
  itens: { type: Array, default: () => [] }, // [{ nome, valor, cor }]
  rotulo: { type: String, default: 'cadastros' },
  vazio: { type: String, default: 'Nenhum dado no período.' },
});

const R = 15.915;
const fmtPct = (v) => v.toLocaleString('pt-BR', { maximumFractionDigits: 1 }) + '%';

const fatias = computed(() => {
  const lista = props.itens.filter(i => i.valor > 0);
  const total = lista.reduce((a, i) => a + i.valor, 0);
  let offset = 25;
  return {
    total,
    itens: lista.map(i => {
      const pct = total ? i.valor / total * 100 : 0;
      const item = { ...i, pct, dash: `${pct} ${100 - pct}`, offset };
      offset -= pct;
      return item;
    }),
  };
});
</script>

<template>
  <div>
    <div class="relative mx-auto my-5 h-40 w-40">
      <svg viewBox="0 0 42 42" class="h-full w-full">
        <circle cx="21" cy="21" :r="R" fill="none" stroke="#f1f5f9" stroke-width="6" />
        <circle v-for="f in fatias.itens" :key="f.nome" cx="21" cy="21" :r="R" fill="none"
          :stroke="f.cor" stroke-width="6" :stroke-dasharray="f.dash" :stroke-dashoffset="f.offset" />
      </svg>
      <div class="absolute inset-0 grid place-items-center text-center">
        <div>
          <p class="text-3xl font-extrabold leading-none text-gray-900">{{ fatias.total }}</p>
          <p class="mt-1 text-xs text-gray-500">{{ rotulo }}</p>
        </div>
      </div>
    </div>
    <ul class="space-y-2 text-sm">
      <li v-for="f in fatias.itens" :key="f.nome" class="flex items-center gap-2">
        <span class="h-2.5 w-2.5 shrink-0 rounded-sm" :style="{ background: f.cor }" />
        <span class="flex-1 truncate font-medium text-gray-700" :title="f.nome">{{ f.nome }}</span>
        <span class="w-10 text-right font-bold text-gray-900">{{ f.valor }}</span>
        <span class="w-14 text-right text-gray-500">{{ fmtPct(f.pct) }}</span>
      </li>
      <li v-if="!fatias.itens.length" class="text-center text-xs text-gray-400">{{ vazio }}</li>
    </ul>
  </div>
</template>
