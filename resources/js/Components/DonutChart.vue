<script setup>
import { computed } from "vue";

const props = defineProps({
  data: { type: Array, default: () => [] }, // [{ label, percentage, color }]
  size: { type: Number, default: 140 },
});

const colors = ["#0891b2", "#6366f1", "#10b981", "#f59e0b", "#ef4444", "#8b5cf6"];

const segments = computed(() => {
  const r = 45;
  const cx = 60;
  const cy = 60;
  const circumference = 2 * Math.PI * r;

  let offset = 0;
  return props.data.map((item, i) => {
    const pct    = item.percentage / 100;
    const dash   = pct * circumference;
    const gap    = circumference - dash;
    const rotate = (offset / 100) * 360 - 90;
    offset      += item.percentage;

    return {
      ...item,
      color:           item.color ?? colors[i % colors.length],
      strokeDasharray: `${dash} ${gap}`,
      rotate,
    };
  });
});
</script>

<template>
  <div class="flex items-center gap-4">
    <svg :width="size" :height="size" :viewBox="`0 0 120 120`">
      <circle cx="60" cy="60" r="45" fill="none" stroke="#f1f5f9" stroke-width="18" />
      <circle
        v-for="(seg, i) in segments"
        :key="i"
        cx="60" cy="60" r="45"
        fill="none"
        :stroke="seg.color"
        stroke-width="18"
        :stroke-dasharray="seg.strokeDasharray"
        :transform="`rotate(${seg.rotate} 60 60)`"
        stroke-linecap="butt"
      />
      <text x="60" y="65" text-anchor="middle" class="text-xs font-bold" style="font-size:12px; fill:#374151">
        {{ data.reduce((s, d) => s + d.total, 0) }}
      </text>
    </svg>

    <div class="space-y-1.5">
      <div v-for="(seg, i) in segments" :key="i" class="flex items-center gap-2 text-xs">
        <span class="w-3 h-3 rounded-sm flex-shrink-0" :style="{ background: seg.color }" />
        <span class="text-gray-600">{{ seg.label }}</span>
        <span class="font-semibold text-gray-800 ml-auto pl-2">{{ seg.percentage }}%</span>
      </div>
    </div>
  </div>
</template>
