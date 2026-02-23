<script setup>
import { computed } from "vue"
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/Components/ui/card"

const props = defineProps({
  title: { type: String, default: "" },
  description: { type: String, default: "" },
  data: {
    type: Array,
    required: true,
  },
  color: { type: String, default: "bg-primary" },
})

const maxValue = computed(() => {
  return Math.max(...props.data.map((d) => d.value), 1)
})

const yTicks = computed(() => {
  const max = maxValue.value
  const step = Math.ceil(max / 4)
  return [step * 4, step * 3, step * 2, step, 0]
})
</script>

<template>
  <Card>
    <CardHeader v-if="title || description" class="pb-2 mb-8">
      <CardTitle class="text-sm font-medium">{{ title }}</CardTitle>
      <CardDescription v-if="description">{{ description }}</CardDescription>
    </CardHeader>
    <CardContent class="pb-4">
      <div class="relative flex h-44">
        <div class="flex flex-col justify-between pr-2 text-[10px] text-muted-foreground w-8 text-right">
          <span v-for="tick in yTicks" :key="tick">{{ tick }}</span>
        </div>

        <div class="flex-1 flex items-end gap-1 border-l border-b border-border pl-2 pb-5 relative">
          <div
            v-for="(item, i) in data"
            :key="i"
            class="flex-1 flex flex-col items-center justify-end h-full group relative"
          >
            <div class="absolute -top-8 opacity-0 group-hover:opacity-100 transition-opacity text-[10px] font-medium bg-popover text-popover-foreground border rounded px-1.5 py-0.5 shadow-sm whitespace-nowrap z-10">
              {{ item.value }}
            </div>

            <div
              :class="[color, 'w-full max-w-8 rounded-t transition-all duration-300 hover:opacity-80']"
              :style="{ height: (item.value / maxValue) * 100 + '%' }"
            />

            <span class="absolute -bottom-4 text-[10px] text-muted-foreground">
              {{ item.label }}
            </span>
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
