<script setup>
import { computed } from 'vue';

// Linha do total acumulado mês a mês; o último ponto fica em destaque.
const props = defineProps({
  valores: { type: Array, default: () => [] }, // acumulado por mês
  labels: { type: Array, default: () => [] },
});

const LW = 460, LH = 160, LPAD = { l: 30, r: 12, t: 14, b: 26 };

const max = computed(() => {
  const m = Math.max(...props.valores, 1);
  return Math.ceil(m / 4) * 4 || 4;
});
const ticks = computed(() => [0, 1, 2, 3, 4].map(i => max.value / 4 * i));
const x = (i) => LPAD.l + (LW - LPAD.l - LPAD.r) * (props.valores.length > 1 ? i / (props.valores.length - 1) : 0);
const y = (v) => LH - LPAD.b - (LH - LPAD.t - LPAD.b) * (v / max.value);
const pontos = computed(() => props.valores.map((v, i) => `${x(i)},${y(v)}`).join(' '));
const area = computed(() => {
  const n = props.valores.length;
  if (!n) return '';
  return `${x(0)},${y(0)} ${pontos.value} ${x(n - 1)},${y(0)}`;
});
const ultimo = computed(() => props.valores.length - 1);
</script>

<template>
  <svg :viewBox="`0 0 ${LW} ${LH}`" class="w-full h-auto">
    <g v-for="t in ticks" :key="t">
      <line :x1="LPAD.l" :x2="LW - LPAD.r" :y1="y(t)" :y2="y(t)" stroke="#e5e7eb" stroke-width="0.6" />
      <text :x="LPAD.l - 8" :y="y(t) + 3" text-anchor="end" font-size="10" fill="#4b5563">{{ Math.round(t) }}</text>
    </g>
    <polygon :points="area" fill="#22b8cf" fill-opacity="0.18" />
    <polyline :points="pontos" fill="none" stroke="#22b8cf" stroke-width="2" stroke-linejoin="round" />
    <g v-for="(v, i) in valores" :key="i">
      <template v-if="v > 0 && (i === 0 || v !== valores[i - 1])">
        <circle :cx="x(i)" :cy="y(v)" r="3.5"
          :fill="i === ultimo ? '#e11d2e' : '#fff'"
          :stroke="i === ultimo ? '#e11d2e' : '#22b8cf'" stroke-width="1.5" />
        <text :x="x(i) - (i === ultimo ? 10 : 0)" :y="y(v) - 7" text-anchor="middle" font-size="11" font-weight="700" fill="#111827">{{ v }}</text>
      </template>
    </g>
    <text v-for="(_, i) in valores" :key="'l' + i" :x="x(i)" :y="LH - 8" text-anchor="middle" font-size="10" fill="#4b5563">{{ labels[i] }}</text>
  </svg>
</template>
