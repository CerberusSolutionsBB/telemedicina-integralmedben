<script setup>
import IconeLucide from "@/Components/Icone/IconeLucide.vue";
import { Banknote, Briefcase } from "lucide-vue-next";

/**
 * Resumo do processo em etiquetas: valor e serviços.
 * Cada etiqueta tem ícone + texto (a informação não depende só da cor).
 */
defineProps({
    item: { type: Object, required: true },
});

const etiqueta = "inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-medium";
</script>

<template>
    <div class="mt-1.5 flex flex-wrap gap-1.5">
        <span :class="[etiqueta, 'border-cyan-200 bg-cyan-50 text-cyan-800 dark:border-cyan-900']">
            <Banknote class="h-3 w-3" aria-hidden="true" /> {{ item.valor_formatado }}
        </span>
        <span v-for="servico in item.servicos" :key="servico.id" :class="[etiqueta, 'border-gray-200 bg-white text-gray-700']">
            <IconeLucide :nome="servico.icone" class="h-3 w-3" /> {{ servico.titulo }}
        </span>
        <span v-if="!item.servicos?.length" :class="[etiqueta, 'border-gray-200 bg-white text-gray-600']">
            <Briefcase class="h-3 w-3" aria-hidden="true" /> Sem serviços
        </span>
    </div>
</template>
