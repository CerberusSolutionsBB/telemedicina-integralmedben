<script setup>
import IconeLucide from "@/Components/Icone/IconeLucide.vue";
import { CalendarClock, Layers, Percent, Ticket } from "lucide-vue-next";

/**
 * Resumo do cupom de benefício em etiquetas: desconto, convênio, serviço, plano e prazo.
 * Cada etiqueta tem ícone + texto (a informação não depende só da cor).
 */
defineProps({
    item: { type: Object, required: true },
});

const etiqueta = "inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-medium";
</script>

<template>
    <div class="mt-1.5 flex flex-wrap gap-1.5">
        <span v-if="item.codigo" :class="[etiqueta, 'border-gray-300 bg-white font-mono font-bold tracking-wider text-gray-900']" title="Código do cupom">
            <Ticket class="h-3 w-3" aria-hidden="true" /> {{ item.codigo }}
        </span>
        <span :class="[etiqueta, 'border-cyan-200 bg-cyan-50 text-cyan-800 dark:border-cyan-900']">
            <Percent class="h-3 w-3" aria-hidden="true" /> {{ item.desconto }}
        </span>
        <span v-if="item.convenio" :class="[etiqueta, 'border-gray-200 bg-white text-gray-700']">
            <IconeLucide :nome="item.convenio.icone" class="h-3 w-3" /> {{ item.convenio.titulo }}
        </span>
        <span v-if="item.servico" :class="[etiqueta, 'border-gray-200 bg-white text-gray-700']">
            <IconeLucide :nome="item.servico.icone" class="h-3 w-3" /> {{ item.servico.titulo }}
        </span>
        <span :class="[etiqueta, 'border-gray-200 bg-white text-gray-700']">
            <Layers class="h-3 w-3" aria-hidden="true" /> {{ item.plano ?? 'Qualquer plano' }}
        </span>
        <span :class="[etiqueta, item.vencido ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300' : 'border-gray-200 bg-white text-gray-700']">
            <CalendarClock class="h-3 w-3" aria-hidden="true" />
            {{ item.prazo_formatado ? (item.vencido ? `Vencido em ${item.prazo_formatado}` : `Até ${item.prazo_formatado}`) : 'Sem prazo' }}
        </span>
    </div>
</template>
